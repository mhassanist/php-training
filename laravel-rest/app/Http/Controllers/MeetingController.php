<?php

namespace App\Http\Controllers;

use App\Meeting;
use Carbon\Carbon;
use Illuminate\Http\Request;

use JWTAuth;

class MeetingController extends Controller
{
    public function __construct(){

        $this->middleware('jwt.auth', [ 'only' =>[
          'store','destroy','update'
        ]]);

    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // return list of all meetings
        $meetings = Meeting::all();
        foreach ($meetings as $meeting){
            $meeting->view_meeting = [
                'url' => 'api/v1/meeting/{$meeting->id}',
                'method' => 'GET'
            ];

        }

// prepare the response
        $response = [
            'msg' => 'List of all meetings',
            'meeting' => $meetings

        ];
        return response()->json($response, 200);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // validate request

        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'time' => 'required | date_format:YmdHie'
        ]);

        if(! $user = JWTAuth::parseToken()->authenticate()){

            return response()->json(['msg' => 'User not found'] ,404);
        }

        // get request from client
        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        //$user_id = $request->input('user_id');
        $user_id = $user->id;

        //store it in DB

        $meeting = new Meeting(array(
            'title' => $title,
            'description' => $description,
            'time' => Carbon::createFromFormat('YmdHie', $time)
        ));

        if($meeting->save()){
            $meeting->users()->attach($user_id);
            $meeting->view_meeting = [
                'url' => 'api/v1/meeting/{$meeting->id}',
                'method' => 'GET'
            ];
            //return response
            $response = [
                'msg' => 'Meeting Created',
                'meeting' => $meeting
            ];

            return response()->json($response, 201);

        }

        $response = [
            'msg' => 'Meeting Created Failed'

        ];
        return response()->json($response, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        $meeting = Meeting::with('users')->where('id', $id)->firstOrFail();
        $meeting->view_meeting = [
                'url' => 'api/v1/meeting/1',
                'method' => 'GET'
            ];

        $response = [
            'msg' => 'Meeting information',
            'meeting' => $meeting

        ];
        return response()->json($response, 200);
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //

        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'time' => 'required | date_format:YmdHie'

        ]);

        if(! $user = JWTAuth::parseToken()->authenticate()){

            return response()->json(['msg' => 'User not found'] ,404);
        }

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        //$user_id = $request->input('user_id');

        $user_id = $user->id;


        //prepare response

//        $meeting = [
//            'title' => $title,
//            'description' => $description,
//            'time' => $time,
//            'user_id' => $user_id,
//            'view_meeting' => [
//                'url' => 'api/v1/meeting/1',
//                'method' => 'GET'
//            ]
//        ];

        $meeting = Meeting::with('users')->findOrFail($id); // check if there is a meeting with this ID
        // check if the passed user_id is registered to this meeting
        if(!$meeting->users()->where('users.id', $user_id)->first()){
            return response()->json(['msg'=>'User Not registered for meeting, Update not Successfully'], 401);
        }

        $meeting->title = $title;
        $meeting->description = $description;
        $meeting->time = Carbon::createFromFormat('YmdHie', $time);

        if(!$meeting->update()){
            return response()->json(['msg'=>'Error During Update'], 404);
        }

        $meeting->view_meeting = [
               'url' => 'api/v1/meeting/{$meeting->id}',
               'method' => 'GET'
            ];


        $response = [
            'msg' => 'Meeting Updated',
            'meeting' => $meeting,

        ];
        return response()->json($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $meeting = Meeting::findOrFail($id); // get meeting object

        if(! $user = JWTAuth::parseToken()->authenticate()){

            return response()->json(['msg' => 'User not found'] ,404);
        }

        if(!$meeting->users()->where('users.id', $user->id)->first()){
            return response()->json(['msg'=>'User Not registered for meeting, Delete not Successfully'], 401);
        }


        $users = $meeting->users; // get all users who attached to the meeting
        $meeting->users()->detach(); // detach all of them

        if(!$meeting->delete()){

            foreach($users as $user){
                $meeting->users()->attach($user);
            }

            return response()->json(['msg'=> 'deletion Failed'], 404);
        }
        $response = [
            'msg' => 'Meeting Deleted',
            'Create' => [
                'href' => 'api/v1/meeting',
                'method' => 'POST',
                'params' => 'title, description, time'

            ]

        ];
        return response()->json($response, 200);
    }
}
