import re
import ast

ts_file = r'c:\Users\user\.antigravity\capstone project\sikap\src\constants\locations.ts'
with open(ts_file, 'r', encoding='utf-8') as f:
    content = f.read()

match = re.search(r'export const BARANGAYS_BY_MUNICIPALITY: Record<string, string\[\]> = (\{.*?\});', content, re.DOTALL)
if not match:
    exit(1)

json_str = match.group(1)
php_array_str = json_str.replace('{', '[').replace('}', ']').replace(':', ' => ')

seeder_content = f"""<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Municipality;
use App\Models\Barangay;

class LocationSeeder extends Seeder
{{
    public function run(): void
    {{
        // Truncate tables to allow re-running
        DB::statement('PRAGMA foreign_keys=OFF;');
        Municipality::truncate();
        Barangay::truncate();
        DB::statement('PRAGMA foreign_keys=ON;');

        $data = {php_array_str};

        foreach ($data as $municipalityName => $barangays) {{
            $municipality = Municipality::create(['name' => $municipalityName]);
            foreach ($barangays as $barangayName) {{
                Barangay::create([
                    'municipality_id' => $municipality->id,
                    'name' => $barangayName
                ]);
            }}
        }}
    }}
}}
"""

with open(r'c:\Users\user\.antigravity\capstone project\sikap-backend\database\seeders\LocationSeeder.php', 'w', encoding='utf-8') as f:
    f.write(seeder_content.replace('$', '$'))
