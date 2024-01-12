<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
class FaqController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json([
            'data' => Faq::all(),
            'status'=>true, 
            'message'=>'Faqs Retrieved Successfully', 
        ]);
    }
   
    public function support()
    {
        DB::table('supports')->insert([
            'description' => " Lorem ipsum dolor sit amet consectetur adipisicing elit. Officia architecto dicta harum veniam, sapiente maxime quae cumque ipsam exercitationem
             iusto reiciendis ullam cupiditate, vel doloribus fugit asperiores, voluptatibus hic blanditiis?"
        ]);
        return response()->json([
            'status'=>true, 
            'message'=>'Feedback Submitted Successfully', 
        ]);
    }
    public function subscriptionRenewal(Request $request)
    {
          DB::table('subscription_renewal')->insert([
            'tokens' => $request->tokens,
            'price'  => $request->price,
          ]);
          return response()->json([
            'status'=>true, 
            'message'=>'Subscription Added Successfully', 
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function show(Faq $faq)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function edit(Faq $faq)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Faq $faq)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function destroy(Faq $faq)
    {
        //
    }
}
