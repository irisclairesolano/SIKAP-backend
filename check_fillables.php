<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$models = Illuminate\Support\Facades\File::allFiles(app_path('Models'));
$ignoreColumns = ['id', 'created_at', 'updated_at', 'deleted_at', 'email_verified_at', 'remember_token'];

foreach ($models as $file) {
    $class = 'App\\Models\\' . $file->getFilenameWithoutExtension();
    if (class_exists($class)) {
        try {
            $model = new $class;
            $table = $model->getTable();
            $fillable = $model->getFillable();
            $columns = Illuminate\Support\Facades\Schema::getColumnListing($table);
            
            $columnsToCheck = array_diff($columns, $ignoreColumns);
            $missingInFillable = array_diff($columnsToCheck, $fillable);
            
            if (!empty($missingInFillable)) {
                echo $class . " (table: " . $table . ") has columns NOT in $fillable: " . implode(', ', $missingInFillable) . "\n";
            }
        } catch (\Exception $e) {
            // Ignore
        }
    }
}
echo "Reverse check complete.\n";
