<?php

namespace App\Http\Controllers;

use App\Models\Ward;
use Illuminate\Http\Request;

class WardController extends Controller
{
    /**
     * Get wards by LGA ID
     */
    public function getWardsByLga($lgaId)
    {
        try {
            $wards = Ward::where('lga_id', $lgaId)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'wards' => $wards
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching wards: ' . $e->getMessage()
            ], 500);
        }
    }
}
