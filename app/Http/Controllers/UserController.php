<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use App\Models\User;
use App\Models\UserOrder;
use App\Models\UserSessions;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class UserController extends Controller
{

    public function login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $check = User::where([
            ['email', '=', $email],
        ])->first();

        if (!$check || !password_verify($password, $check->password)) return response([
            'status' => 403,
            'message' => 'Email or password does not exist',
            'data' => [
                'email' => $email,
                'password' => $password
            ]
        ], 403);

        $token = Str::random(64);
        $new_session = new UserSessions;

        $new_session->user_uuid = $check->user_uuid;
        $new_session->token = $token;
        $new_session->user_agent = $request->header('user-agent');

        $new_session->save();
        return response([
            'status' => 200,
            'message' => 'success',
            'data' => [
                'user_uuid' => $check->user_uuid,
                'token' => $token
            ]
        ]);
    }

    public function register(Request $request)
    {
        $email = $request->input('email');
        $name = $request->input('name');
        $password = $request->input('password');

        // This request is fully validated by frontend
        if (!$email || !$password) return response([
            'status' => 400,
            'message' => 'Bad request'
        ], 400);

        // Email duplication validation
        $email_check = User::where('email', '=', $email)->first();

        if ($email_check) return response([
            'status' => 409,
            'message' => 'Email has already been taken'
        ], 409);

        $new_user = new User;

        $new_user->user_uuid = Uuid::uuid4();
        $new_user->name = $name;
        $new_user->email = $email;
        $new_user->password = password_hash($password, PASSWORD_DEFAULT);

        $new_user->save();

        return $this->login($request);
    }

    public function getInitialData()
    {
        $user = User::select(['name', 'email'])->where('user_uuid', '=', USER_UUID)->first();
        $unpaid_orders = UserOrder::where([
            ['user_uuid', '=', USER_UUID],
            ['status', '=', 0]
        ])->get();

        
        $user->unpaid_orders = sizeof($unpaid_orders);
        
        foreach ($unpaid_orders as $order) {
            $timestamp = strtotime($order->created_at);
            
            if (time() - $timestamp > 5 * 60) {
                UserOrder::where('order_no', '=', $order->order_no)->update([
                    'status' => 2
                ]);
                
                $user->unpaid_orders --;
            }
        }

        return response([
            'status' => 200,
            'message' => 'success',
            'data' => $user
        ]);
    }

    public function getOrders()
    {
        $orders = UserOrder::where('user_uuid', '=', USER_UUID)->orderBy('created_at', 'DESC')->get()->makeHidden(['user_uuid']);

        return response([
            'status' => 200,
            'message' => 'success',
            'data' => $orders
        ]);
    }
}
