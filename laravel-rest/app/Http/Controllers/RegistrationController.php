<?php

namespace App\Http\Controllers;

use App\Meeting;
use App\User;
use Illuminate\Http\Request;

use JWTAuth;

class RegistrationController extends Controller
{
    public function __construct(){

        $this->middleware('jwt.auth');

    }

    public function store(Request $request)
    {
        // validate the request from client

        $request->validate([
            'meeting_id' => 'required',
            'user_id' => 'required',

        ]);

        $meeting_id = $request->input('meeting_id');
        $user_id = $request->input('user_id');

        $meeting = Meeting::findOrFail($meeting_id);
        $user = User::findOrFail($user_id);

        $message = [
            'msg' => 'User is already registered to for meeting',
            'meeting' => $meeting,
            'user'  => $user,
            'Unregister' => [
                'href' => 'api/v1/meeting/registration/{$meeting->id}',
                'Method' => 'DELETE',
                'params' => 'meeting_id, user_id'
            ]
        ];

        if($meeting->users()->where('users.id', $user->id)->first()){
            return response()->json($message,404);
        }

        $user->meetings()->attach($meeting);

        $response = [
            'msg' => 'User registered for meeting',
            'meeting' => $meeting,
            'user' => $user,
            'unregister' => [
                'href' => 'api/v1/meeting/registration/{$meeting->id}',
                'method' => 'DELETE'
            ]

        ];
        return response()->json($response, 201);

    }

    // detach (unregister) log in user from the course
    public function destroy($id)
    {

        // Meeting ID
        $meeting = Meeting::findOrFail($id);

        if(! $user = JWTAuth::parseToken()->authenticate()){

            return response()->json(['msg' => 'User not found'] ,404);
        }

        if(!$meeting->users()->where('users.id', $user->id)->first()){
            return response()->json(['msg'=>'User Not registered for meeting, Delete operation not Successful'], 401);
        }

        $meeting->users()->detach($user->id);

        $response = [
            'msg' => 'Users unregistered for meeting',
            'meeting' => $meeting,
            'user' => $user,
            'register' => [
                'href' => 'api/v1/meeting/registration',
                'method' => 'POST',
                'param' => 'user_id, meeting_id'
            ]

        ];
        return response()->json($response, 200);
    }
}
