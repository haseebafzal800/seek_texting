<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\Categories;

use App\Http\Controllers\Controller;

class CategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // dd('ssssssss');
        $resp = array();
        $d = Categories::where(['status' => 'active'])->get();
        if ($d) {
            $resp = [
                'data' => $d,
                'status' => true,
                'message' => '',
            ];
        } else {
            $resp = [
                'data' => '',
                'status' => true,
                'message' => 'No Data found',
            ];
        }
        return response()->json($resp);
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
        $validator = \Validator::make($request->all(), [
            'name' => 'required|unique:categories,name,NULL,NULL,deleted_at,NULL'
        ]);
        if ($validator->fails()) {
            $resp = [
                'data' => '',
                'status' => false,
                'message' => $validator->errors()->first(),
            ];
        } else {
            $resp = array();
            $data = new Categories;
            $data->name = $request->name;
            $data->status = $request->status;
            $ok = $data->save();
            if ($ok) {
                $resp = [
                    'data' => $data->id,
                    'status' => true,
                    'message' => 'Category added successfully',
                ];
            } else {
                $resp = [
                    'data' => '',
                    'status' => false,
                    'message' => 'Category added fail',
                ];
            }
        }
        return response()->json($resp);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $d = Categories::where(['id' => $id, 'is_deleted' => '0'])->first();
        if ($d) {
            return response()->json([
                'data' => $d,
                'status' => true,
                'message' => '',
            ]);
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'data not found',
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $ok = Categories::where(['id' => $request->id])->first();
        if ($ok) {
            $done = $ok->update($request->all());
            if ($done) {
                return response()->json([
                    'data' => '',
                    'status' => true,
                    'message' => 'Category updated successfully',
                ]);
            } else {
                return response()->json([
                    'data' => '',
                    'status' => false,
                    'message' => 'Category update fail',
                ]);
            }
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Data not found',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Categories::find($id)->delete();
        if (Categories::find($id)->delete()) {
            return response()->json([
                'data' => '',
                'status' => true,
                'message' => 'Category delete successfully',
            ]);
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Category delete fail',
            ]);
        }
    }
}
