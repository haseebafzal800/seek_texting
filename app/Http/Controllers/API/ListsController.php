<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\Lists;
use App\Models\Contactlist;

use App\Http\Controllers\Controller;

class ListsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($keywords = null)
    {

        $resp = array();
        $rawWhere = "(1=1)";
        if ($keywords) {
            $rawWhere = "(lists.name LIKE '%{$keywords}%')";
        }
        $d = Lists::select('*')->with('categories', function ($q) {
            return $q->select('id', 'name');
        })->where(['user_id' => auth()->user()->id])->whereRaw($rawWhere)->paginate(10);
        if ($d) {
            for ($i = 0; $i < count($d); $i++) {
                $d[$i]->count = $d[$i]->contactlist()->count();
                // $d[$i]->count = Contactlist::where(['list_id'=>$d[$i]->id, 'deleted_at'=>null])->count();
            }

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

    public function getAllLists()
    {
        $resp = array();
        $d = Lists::select('*')->with('categories', function ($q) {
            return $q->select('id', 'name');
        })->where(['user_id' => auth()->user()->id])->get();
        if ($d) {
            for ($i = 0; $i < count($d); $i++) {
                $d[$i]->count = $d[$i]->contactlist()->count();
                // $d[$i]->count = Contactlist::where(['list_id'=>$d[$i]->id, 'deleted_at'=>null])->count();
            }

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
            'name' => 'required|unique:lists,name,NULL,NULL,deleted_at,NULL,user_id,' . auth()->user()->id

        ]);
        if ($validator->fails()) {
            $resp = [
                'data' => '',
                'status' => false,
                'message' => $validator->errors()->first(),
            ];
        } else {
            $resp = array();
            $data = new Lists;
            $data->category_id = $request->category_id;
            $data->name = $request->name;
            $data->status = $request->status ?? 'active';
            $data->user_id = auth()->user()->id;
            $ok = $data->save();
            if ($ok) {
                $resp = [
                    'data' => $data->id,
                    'status' => true,
                    'message' => 'Data added successfully',
                ];
            } else {
                $resp = [
                    'data' => '',
                    'status' => false,
                    'message' => 'Data added fail',
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
        $d = Lists::where(['id' => $id, 'user_id' => auth()->user()->id])->first();
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
        $ok = Lists::where(['id' => $request->id, 'user_id' => auth()->user()->id])->first();
        if ($ok) {
            $done = $ok->update($request->all());
            if ($done) {
                return response()->json([
                    'data' => '',
                    'status' => true,
                    'message' => 'Data updated successfully',
                ]);
            } else {
                return response()->json([
                    'data' => '',
                    'status' => false,
                    'message' => 'Data update fail',
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
        if (Lists::find($id)->delete()) {
            Contactlist::where('list_id', $id)->update(['deleted_at' => now()]);
            return response()->json([
                'data' => '',
                'status' => true,
                'message' => 'Success! Data deleted',
            ]);
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Error! Data delete fail',
            ]);
        }
    }
}
