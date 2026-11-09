<?php

declare(strict_types=1);

/**
 * End-to-end smoke test for SMF 2.1.7 and Sitemap for Search Engines.
 *
 * This intentionally goes through the web installer and SMF's package manager
 * rather than copying the modification's files directly into the forum.
 */

function env_required(string $name): string
{
	$value = getenv($name);
	if ($value === false || $value === '')
		throw new RuntimeException("Required environment variable {$name} is missing.");

	return $value;
}

function ensure(bool $condition, string $message): void
{
	if (!$condition)
		throw new RuntimeException($message);
}

function note(string $message): void
{
	fwrite(STDOUT, "[integration] {$message}\n");
}

final class HttpClient
{
	private string $cookieFile;

	public function __construct(string $cookieFile)
	{
		$this->cookieFile = $cookieFile;
		@unlink($this->cookieFile);
	}

	/** @return array{status:int, headers:array<string,string>, body:string} */
	public function request(string $url, ?array $post = null): array
	{
		$headers = [];
		$handle = curl_init($url);
		ensure($handle !== false, 'Could not initialize cURL.');

		curl_setopt_array($handle, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 60,
			CURLOPT_COOKIEJAR => $this->cookieFile,
			CURLOPT_COOKIEFILE => $this->cookieFile,
			CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$headers): int {
				if (str_starts_with($line, 'HTTP/'))
					$headers = [];
				elseif (str_contains($line, ':'))
				{
					[$name, $value] = explode(':', $line, 2);
					$headers[strtolower(trim($name))] = trim($value);
				}
				return strlen($line);
			},
		]);

		if ($post !== null)
		{
			curl_setopt($handle, CURLOPT_POST, true);
			curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($post, '', '&', PHP_QUERY_RFC3986));
		}

		$body = curl_exec($handle);
		if ($body === false)
		{
			$error = curl_error($handle);
			curl_close($handle);
			throw new RuntimeException("HTTP request failed for {$url}: {$error}");
		}

		$status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
		curl_close($handle);
		ensure($status >= 200 && $status < 400, "Unexpected HTTP {$status} for {$url}.");

		return ['status' => $status, 'headers' => $headers, 'body' => $body];
	}
}

function assert_installer_page(array $response, string $stage): void
{
	$body = $response['body'];
	ensure(!str_contains($body, 'class="errorbox"'), "SMF installer reported an error during {$stage}.");
	ensure(!str_contains($body, 'class="red"'), "SMF database population reported failures during {$stage}.");
}

/** @return array{action:string, fields:array<string,string>} */
function package_install_form(string $html, string $baseUrl): array
{
	$previous = libxml_use_internal_errors(true);
	$document = new DOMDocument();
	$loaded = $document->loadHTML($html);
	libxml_clear_errors();
	libxml_use_internal_errors($previous);
	ensure($loaded, 'Could not parse the package manager response.');

	$xpath = new DOMXPath($document);
	$form = $xpath->query('//form[@id="view_package"]')->item(0);
	ensure($form instanceof DOMElement, 'SMF package manager did not show the installation form.');

	$action = html_entity_decode($form->getAttribute('action'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
	ensure(str_contains($action, 'sa=install2'), 'SMF package manager did not offer to install the package.');
	if (!preg_match('~^https?://~i', $action))
		$action = rtrim($baseUrl, '/') . '/' . ltrim($action, '/');

	$fields = [];
	foreach ($xpath->query('.//input[@type="hidden"]', $form) as $input)
	{
		if ($input instanceof DOMElement && $input->getAttribute('name') !== '')
			$fields[$input->getAttribute('name')] = $input->getAttribute('value');
	}

	ensure(isset($fields['seqnum']), 'SMF package form is missing its one-time sequence number.');
	ensure(count($fields) >= 2, 'SMF package form is missing its session field.');

	return ['action' => $action, 'fields' => $fields];
}

/** @return array{action:string, fields:array<string,string>} */
function login_form(string $html, string $baseUrl): array
{
	$previous = libxml_use_internal_errors(true);
	$document = new DOMDocument();
	$loaded = $document->loadHTML($html);
	libxml_clear_errors();
	libxml_use_internal_errors($previous);
	ensure($loaded, 'Could not parse the SMF login page.');

	$xpath = new DOMXPath($document);
	$form = $xpath->query('//form[@id="frmLogin"]')->item(0);
	ensure($form instanceof DOMElement, 'SMF did not show its login form.');

	$action = html_entity_decode($form->getAttribute('action'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
	if (!preg_match('~^https?://~i', $action))
		$action = rtrim($baseUrl, '/') . '/' . ltrim($action, '/');

	$fields = [];
	foreach ($xpath->query('.//input[@type="hidden"]', $form) as $input)
	{
		if ($input instanceof DOMElement && $input->getAttribute('name') !== '')
			$fields[$input->getAttribute('name')] = $input->getAttribute('value');
	}

	ensure(count($fields) >= 2, 'SMF login form is missing its session or login token.');

	return ['action' => $action, 'fields' => $fields];
}

function fetch_xml(HttpClient $client, string $url, string $expectedRoot): array
{
	$response = $client->request($url);
	$contentType = strtolower($response['headers']['content-type'] ?? '');
	$robots = strtolower($response['headers']['x-robots-tag'] ?? '');
	ensure(str_starts_with($contentType, 'application/xml'), "{$url} did not return application/xml.");
	ensure(str_contains($robots, 'noindex') && str_contains($robots, 'follow'), "{$url} is missing the expected X-Robots-Tag.");

	$previous = libxml_use_internal_errors(true);
	$xml = simplexml_load_string($response['body']);
	$errors = libxml_get_errors();
	libxml_clear_errors();
	libxml_use_internal_errors($previous);
	ensure($xml instanceof SimpleXMLElement, "{$url} did not return well-formed XML: " . json_encode($errors));
	ensure($xml->getName() === $expectedRoot, "{$url} returned <{$xml->getName()}> instead of <{$expectedRoot}>.");

	return [$response, $xml];
}

function load_smf_settings(string $path): array
{
	return (static function (string $settingsPath): array {
		include $settingsPath;
		return [
			'db_type' => $db_type ?? null,
			'db_name' => $db_name ?? null,
			'db_prefix' => $db_prefix ?? null,
		];
	})($path);
}

/** @return array<string,string> */
function database_assertions(string $dbType, string $host, int $port, string $name, string $user, string $password): array
{
	$settings = [];
	$package = null;

	if ($dbType === 'postgresql')
	{
		$connection = pg_connect("host={$host} port={$port} dbname={$name} user={$user} password={$password}");
		ensure($connection !== false, 'Could not connect to PostgreSQL for assertions.');
		$packageResult = pg_query($connection, "SELECT version, install_state, failed_steps FROM smf_log_packages WHERE package_id = 'matthiasfeik:sitemapforsearchengines' ORDER BY id_install DESC LIMIT 1");
		ensure($packageResult !== false, 'Could not query the PostgreSQL package log.');
		$package = pg_fetch_assoc($packageResult) ?: null;
		$settingsResult = pg_query($connection, "SELECT variable, value FROM smf_settings WHERE variable IN ('integrate_actions', 'integrate_general_mod_settings', 'sitemap_topic_count', 'sitemap_cache_ttl')");
		ensure($settingsResult !== false, 'Could not query PostgreSQL settings.');
		while ($row = pg_fetch_assoc($settingsResult))
			$settings[$row['variable']] = $row['value'];
		pg_close($connection);
	}
	elseif ($dbType === 'mysql')
	{
		$connection = mysqli_init();
		ensure($connection !== false, 'Could not initialize mysqli.');
		ensure($connection->real_connect($host, $user, $password, $name, $port), 'Could not connect to MySQL for assertions.');
		$packageResult = $connection->query("SELECT version, install_state, failed_steps FROM smf_log_packages WHERE package_id = 'matthiasfeik:sitemapforsearchengines' ORDER BY id_install DESC LIMIT 1");
		ensure($packageResult !== false, 'Could not query the MySQL package log.');
		$package = $packageResult->fetch_assoc();
		$settingsResult = $connection->query("SELECT variable, value FROM smf_settings WHERE variable IN ('integrate_actions', 'integrate_general_mod_settings', 'sitemap_topic_count', 'sitemap_cache_ttl')");
		ensure($settingsResult !== false, 'Could not query MySQL settings.');
		while ($row = $settingsResult->fetch_assoc())
			$settings[$row['variable']] = $row['value'];
		$connection->close();
	}
	else
		throw new RuntimeException("Unsupported DB_TYPE {$dbType}.");

	ensure(is_array($package), 'The mod is absent from SMF\'s package log.');
	ensure($package['version'] === '1.2.0', 'Unexpected installed package version.');
	ensure((int) $package['install_state'] === 1, 'SMF did not record a completed package installation.');
	$failedSteps = json_decode((string) $package['failed_steps'], true);
	ensure(is_array($failedSteps) && $failedSteps === [], 'SMF recorded failed package installation steps.');
	ensure(str_contains($settings['integrate_actions'] ?? '', 'SitemapActions'), 'The integrate_actions hook was not registered.');
	ensure(str_contains($settings['integrate_general_mod_settings'] ?? '', 'SitemapGeneralModSettings'), 'The settings hook was not registered.');
	ensure(($settings['sitemap_topic_count'] ?? null) === '1000', 'The default topic count setting was not stored.');
	ensure(($settings['sitemap_cache_ttl'] ?? null) === '3600', 'The default cache TTL setting was not stored.');

	return $settings;
}

try
{
	$root = env_required('SMF_ROOT');
	$baseUrl = rtrim(env_required('SMF_BASE_URL'), '/');
	$dbType = env_required('DB_TYPE');
	$dbHost = env_required('DB_HOST');
	$dbPort = (int) env_required('DB_PORT');
	$dbName = env_required('DB_NAME');
	$dbUser = env_required('DB_USER');
	$dbPassword = env_required('DB_PASSWORD');
	$adminPassword = env_required('SMF_ADMIN_PASSWORD');
	$diagnostics = rtrim((string) getenv('RUNNER_TEMP'), DIRECTORY_SEPARATOR) . '/smf-ci';
	@mkdir($diagnostics, 0777, true);

	$installer = new HttpClient($diagnostics . '/installer-cookies.txt');
	$response = $installer->request($baseUrl . '/install.php?step=0', ['contbutt' => 'Continue']);
	assert_installer_page($response, 'welcome and file checks');
	file_put_contents($diagnostics . '/install-step-2.html', $response['body']);

	$response = $installer->request($baseUrl . '/install.php?step=2', [
		'db_type' => $dbType,
		'db_server' => $dbHost,
		'db_port' => (string) $dbPort,
		'db_name' => $dbName,
		'db_user' => $dbUser,
		'db_passwd' => $dbPassword,
		'db_prefix' => 'smf_',
	]);
	assert_installer_page($response, 'database settings');
	file_put_contents($diagnostics . '/install-step-3.html', $response['body']);

	$response = $installer->request($baseUrl . '/install.php?step=3', [
		'boardurl' => $baseUrl,
		'mbname' => 'SMF Sitemap CI',
	]);
	assert_installer_page($response, 'forum settings and database population');
	file_put_contents($diagnostics . '/install-step-4.html', $response['body']);

	$response = $installer->request($baseUrl . '/install.php?step=4', ['pop_done' => '1', 'contbutt' => 'Continue']);
	assert_installer_page($response, 'database population confirmation');

	$response = $installer->request($baseUrl . '/install.php?step=5', [
		'username' => 'ci-admin',
		'password1' => $adminPassword,
		'password2' => $adminPassword,
		'email' => 'ci-admin@example.invalid',
		'server_email' => 'ci-server@example.invalid',
		'contbutt' => 'Continue',
	]);
	assert_installer_page($response, 'administrator creation');
	ensure(str_contains($response['body'], 'Congratulations'), 'SMF installer did not reach its completion page.');
	file_put_contents($diagnostics . '/install-complete.html', $response['body']);
	note("Installed SMF 2.1.7 with {$dbType} through its web installer.");

	foreach (['install.php', 'install_2-1_mysql.sql', 'install_2-1_postgresql.sql'] as $installerFile)
	{
		$path = $root . '/' . $installerFile;
		ensure(!file_exists($path) || unlink($path), "Could not remove {$installerFile} after installation.");
	}

	$settings = load_smf_settings($root . '/Settings.php');
	ensure($settings['db_type'] === $dbType, "Settings.php contains {$settings['db_type']} instead of {$dbType}.");
	ensure($settings['db_name'] === $dbName && $settings['db_prefix'] === 'smf_', 'Settings.php contains unexpected database settings.');

	$admin = new HttpClient($diagnostics . '/admin-cookies.txt');
	$response = $admin->request($baseUrl . '/index.php?action=login');
	$form = login_form($response['body'], $baseUrl);
	$response = $admin->request($form['action'], array_merge($form['fields'], [
		'user' => 'ci-admin',
		'passwrd' => $adminPassword,
		'cookielength' => '-1',
	]));
	file_put_contents($diagnostics . '/login-response.html', $response['body']);
	ensure(!str_contains($response['body'], 'class="errorbox"'), 'SMF administrator login failed.');
	ensure(str_contains($response['body'], 'action=logout'), 'SMF did not return an authenticated page after login.');

	$packageName = 'sitemap-for-search-engines.tar.gz';
	$response = $admin->request($baseUrl . '/index.php?action=admin;area=packages;sa=install;package=' . rawurlencode($packageName));
	file_put_contents($diagnostics . '/package-preview.html', $response['body']);
	ensure(!str_contains($response['body'], 'class="errorbox"'), 'SMF package manager rejected the package preview.');
	$form = package_install_form($response['body'], $baseUrl);
	$response = $admin->request($form['action'], $form['fields']);
	file_put_contents($diagnostics . '/package-install.html', $response['body']);
	ensure(!str_contains($response['body'], 'class="errorbox"'), 'SMF package manager reported an installation error.');
	note('Installed the modification through SMF\'s package manager.');

	database_assertions($dbType, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
	note('Verified the package log, hooks, and default settings in the selected database.');

	$public = new HttpClient($diagnostics . '/public-cookies.txt');
	$html = $public->request($baseUrl . '/index.php?action=sitemap');
	ensure(str_contains(strtolower($html['headers']['content-type'] ?? ''), 'text/html'), 'HTML sitemap did not return text/html.');
	ensure(str_contains($html['body'], '?board=1.0'), 'HTML sitemap did not render the installed public board.');

	[, $index] = fetch_xml($public, $baseUrl . '/index.php?action=sitemap;sa=xml', 'sitemapindex');
	$indexLocations = array_map('strval', $index->xpath('//*[local-name()="loc"]') ?: []);
	ensure(count($indexLocations) === 2, 'Sitemap index did not contain exactly the board and topic child sitemaps.');
	ensure(count(array_filter($indexLocations, static fn (string $url): bool => str_contains($url, 'type=boards'))) === 1, 'Sitemap index is missing the board child sitemap.');
	ensure(count(array_filter($indexLocations, static fn (string $url): bool => str_contains($url, 'type=topics') && str_contains($url, 'page=1'))) === 1, 'Sitemap index is missing topic page 1.');

	[, $boards] = fetch_xml($public, $baseUrl . '/index.php?action=sitemap;sa=xml;type=boards', 'urlset');
	$boardLocations = array_map('strval', $boards->xpath('//*[local-name()="loc"]') ?: []);
	ensure(in_array($baseUrl . '/index.php?board=1.0', $boardLocations, true), 'Board sitemap is missing the installed public board.');

	[, $topics] = fetch_xml($public, $baseUrl . '/index.php?action=sitemap;sa=xml;type=topics;page=1', 'urlset');
	$topicLocations = array_map('strval', $topics->xpath('//*[local-name()="loc"]') ?: []);
	ensure(in_array($baseUrl . '/index.php?topic=1.0', $topicLocations, true), 'Topic sitemap is missing SMF\'s installed welcome topic.');
	note('Validated the HTML sitemap, XML index, board sitemap, topic sitemap, and XML response headers.');
}
catch (Throwable $error)
{
	fwrite(STDERR, "[integration] FAILED: {$error->getMessage()}\n");
	exit(1);
}

