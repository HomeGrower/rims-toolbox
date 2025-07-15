<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HotelSetupController extends Controller
{
    public function index()
    {
        return view('hotel-setup'); // This will render the hotel-setup.blade.php view
    }
}