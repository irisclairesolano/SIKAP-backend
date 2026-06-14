$models = File::allFiles(app_path('Models'));
foreach ($models as $file) {
    $class = 'App\\Models\\' . $file->getFilenameWithoutExtension();
    if (class_exists($class)) {
        try {
            $model = new $class;
            $table = $model->getTable();
            $fillable = $model->getFillable();
            $columns = Schema::getColumnListing($table);
            $missing = array_diff($fillable, $columns);
            if (!empty($missing)) {
                echo $class . " (table: " . $table . ") is missing columns: " . implode(', ', $missing) . "\n";
            }
        } catch (\Exception $e) {
            // Ignore abstract classes or traits
        }
    }
}
echo "Check complete.";
