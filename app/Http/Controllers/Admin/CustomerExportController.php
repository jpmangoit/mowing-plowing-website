<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class CustomerExportController extends AdminBaseController
{
    public function export()
    {
        // Execute the SQL query to fetch the data
        $customers = DB::select("
            SELECT DISTINCT
                u.first_name,
                u.last_name,
                u.email,
                u.phone_number,
                u.address,
                CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM recurring_histories rh2
                        WHERE rh2.user_id = u.id AND rh2.status = 'Active'
                    ) THEN 'Active'
                    ELSE 'Not Active'
                END AS recurring_status
            FROM users u
            LEFT JOIN orders o ON o.user_id = u.id
            LEFT JOIN recurring_histories rh ON rh.user_id = u.id
            WHERE u.status = 1
              AND (o.id IS NOT NULL OR rh.id IS NOT NULL);
        ");

        // Define CSV headers
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customers-'.date('Y-m-d').'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        // Create the CSV
        $callback = function() use ($customers) {
            $file = fopen('php://output', 'w');

            // Add headers row
            fputcsv($file, [
                'First Name', 
                'Last Name', 
                'Email', 
                'Phone Number', 
                'Address', 
                'Recurring Status'
            ]);

            // Add data rows
            foreach ($customers as $customer) {
                fputcsv($file, [
                    $customer->first_name,
                    $customer->last_name,
                    $customer->email,
                    $customer->phone_number,
                    $customer->address,
                    $customer->recurring_status
                ]);
            }

            fclose($file);
        };

        // Stream the CSV to the browser
        return Response::stream($callback, 200, $headers);
    }
}
