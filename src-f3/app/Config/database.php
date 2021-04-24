<?php
$db_host = getenv('DB_HOST');
$db_port = getenv('DB_PORT');
$db_user = getenv('DB_USER');
$db_password = getenv('DB_PASS');
$db_name = getenv('DB_NAME');

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name}";

$database_instance = new DB\SQL($dsn, $db_user, $db_password);

$db_version = false;

function run_migration($db_version, $database_instance)
{

    error_log("Running migration for DB version " . $db_version);

    $filename = './migrations/' . $db_version . '.sql';
    // Open the file
    $fp = @fopen($filename, 'r');
    $migration = [];

    $comment_beginnings = ['--', '/*'];

    // Add each line to an array
    if ($fp) {
        $read_migration_lines = explode("\n", fread($fp, filesize($filename)));
    }

    $full_command = '';

    foreach ($read_migration_lines as $line_number => $command) {
        $command_first_two_caracters = substr($command, 0, 2);
        if (!in_array($command_first_two_caracters, $comment_beginnings) && !empty($command)) {

            $full_command .= trim($command);
            $final_command_char = substr($command, -1, 1);

            if ($final_command_char == ';') {
                $migration[] = $full_command;
                $full_command = '';
            }
        }
    }

    $database_instance->exec($migration);

    if ($db_version == 0) {
        $database_instance->exec("INSERT INTO options (name, value) VALUES ('DB_VERSION', '" . $db_version . "');");
    } else {
        $database_instance->exec("UPDATE options SET value='" . $db_version . "' WHERE name='DB_VERSION';");
    }


    return $migration;
}

// Migrations.
// Simply check current defined DB version and update if needed.
$options_table_exists = count($database_instance->exec(
    " SELECT * 
    FROM information_schema.tables
    WHERE table_schema = 'docker' 
    AND table_name = 'options'
    LIMIT 1;"
));

if (!$options_table_exists) {
    // Migrations 0 needs to run.
    run_migration(0, $database_instance);
}

$db_version = $database_instance->exec("SELECT value FROM options WHERE name = 'DB_VERSION'")[0]['value'];

$fi = new FilesystemIterator('./migrations', FilesystemIterator::SKIP_DOTS);
while ($db_version < iterator_count($fi) - 1) {
    $new_db_version = $db_verison + 1;
    if (file_exists('./migrations/' . $new_db_version . '.sql')) {
        run_migration($new_db_version, $database_instance);
        $db_version = $new_db_version;
    }
}

// Count the number of migrations from the current





return $database_instance;
