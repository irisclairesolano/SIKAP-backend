<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$models = Illuminate\Support\Facades\File::allFiles(app_path('Models'));
foreach ($models as $file) {
    $class = 'App\\Models\\' . $file->getFilenameWithoutExtension();
    if (class_exists($class)) {
        try {
            $model = new $class;
            $table = $model->getTable();
            $fillable = $model->getFillable();
            $columns = Illuminate\Support\Facades\Schema::getColumnListing($table);
            $missing = array_diff($fillable, $columns);
            if (!empty($missing)) {
                echo $class . " (table: " . $table . ") is missing columns: " . implode(', ', $missing) . "\n";
            }
        } catch (\Exception $e) {
            // Ignore
        }
    }
}
echo "Check complete.\n";
