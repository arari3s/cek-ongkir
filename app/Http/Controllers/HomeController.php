<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Courier;
use App\Models\Province;
use Illuminate\Http\Request;
use Kavist\RajaOngkir\Facades\RajaOngkir;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $province = $this->getProvince();
        $courier = $this->getCourier();

        return view('home', compact('province', 'courier'));
    }

    public function store(Request $request)
    {
        $couriers = $request->input('courier');

        if ($couriers) {
            $data = [
                'origin'        => $this->getCity($request->origin_city),
                'destination'   => $this->getCity($request->destination_city),
                'weight'        => 1300,
                'result'        => []
            ];

            foreach ($couriers as $courier) {
                $ongkir =  RajaOngkir::ongkosKirim([
                    'origin'        => $data['origin'],         // ID kota/kabupaten asal
                    'destination'   => $data['destination'],    // ID kota/kabupaten tujuan
                    'weight'        => $data['weight'],         // berat barang dalam gram
                    'courier'       => $courier                 // kode kurir pengiriman: ['jne', 'tiki', 'pos'] untuk starter
                ])->get();

                $data['result'][] = $ongkir;
            }

            return view('costs')->with($data);
        }

        return redirect()->back();
    }

    public function getCourier()
    {
        return Courier::all();
    }

    public function getProvince()
    {
        return Province::pluck('title', 'code');
    }

    public function getCity($code)
    {
        return City::where('code', $code)->first();
    }

    public function getCities($id)
    {
        return City::where('province_code', $id)->pluck('title', 'code');
    }

    public function searchCities(Request $request)
    {
        $search = $request->search;

        if (empty($search)) {
            $cities = City::orderBy('title', 'asc')
                ->select('id', 'title')
                ->limit(5)
                ->get();
        } else {
            $cities = City::orderBy('title', 'asc')
                ->where('title', 'like', '%' . $search . '%')
                ->select('id', 'title')
                ->limit(5)
                ->get();
        }

        $response = [];

        foreach ($cities as $city) {
            $response[] = [
                'id' => $city->id,
                'text' => $city->title,
            ];
        }

        return json_encode($response);
    }
}
