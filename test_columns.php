<?php

use Illuminate\Support\Facades\Schema;

try {
    $columns = Schema::getColumnListing('users');
    echo "Columnas de la tabla users:\n";
    print_r($columns);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
