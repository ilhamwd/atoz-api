<?php

namespace App\Http\Controllers;

use App\Models\UserOrder;
use DateTime;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderController extends Controller {

    public function makeOrder(Request $request)
    {
        $type = $request->input('type', 'product');
        [$mobile_number, $product_name, $shipping_address, $price] = array_map(function($input) use ($request) {
            return $request->input($input);
        }, ['mobile_number', 'product_name', 'shipping_address', 'price']);

        $new_order = new UserOrder;
        
        if ($type == 'product') {
            $new_order->type = 1;
            $new_order->product_name = $product_name;
            $new_order->shipping_address = $shipping_address;
            $new_order->shipping_code = Str::random(8);
        }
        else if ($type == 'balance') {
            $new_order->type = 2;
            $new_order->mobile_number = $mobile_number;
        }
        else return response([
            'status' => 400,
            'message' => 'Bad request',
        ], 400);

        $new_order->user_uuid = USER_UUID;
        $new_order->status = 0;
        $new_order->price = $price;
        $order_no = '';
        
        // Generate 10-digit shipping code
        for ($i = 1; $i <= 10; $i ++) {
            $order_no .= mt_rand(0, 9);
        }
        
        try {
            $new_order->order_no = $order_no;
            $new_order->save();
            $new_order->order_no = $order_no;
        } catch (Throwable $e) {
            return response([
                'status' => 400,
                'message' => $e->getMessage()
            ], 400);
        }

        return response([
            'status' => 200,
            'message' => 'success',
            'data' => $new_order
        ]);
    }

    public function payOrder(Request $request)
    {
        $order_no = $request->input('order_no');
        $order = UserOrder::where('order_no', '=', $order_no)->first();
        
        if (!$order || $order->user_uuid != USER_UUID) return response([
            'status' => 400,
            'message' => 'Bad request'
        ], 400);
        elseif ($order->status != 0) return response([
            'status' => 200,
            'message' => 'The order has been paid'
        ]);
        
        $date_now = date("Y-m-d ", time() + TIMEZONE);
        $date_ordered = date("Y-m-d H:i:s", strtotime($order->created_at));
        $time_5pm = $date_now . "10:00:00"; // Original: 17:00:00
        $time_9am = $date_now . "02:00:00"; // Original: 09:00:00
        $successful_rate = .9;
        $query = "SELECT * FROM user_order WHERE created_at > '$time_9am' AND created_at < '$time_5pm'";
        
        if ($date_ordered > $time_5pm || $date_ordered < $time_9am) {
            $query = "SELECT * FROM user_order WHERE created_at < '$time_9am' OR created_at > '$time_5pm'";
            $successful_rate = .4;
        }
        
        $data = DB::select($query);
        [$success_count, $other_count] = [0, 0];

        foreach ($data as $order_data) {
            if ($order_data->status == 1) $success_count ++;
            else $other_count ++;
        }

        $total = $success_count + $other_count;
        $total = $total ? $total : 1; // Avoid zero division error
        $status = ($success_count / $total) >= $successful_rate ? 3 : 1;
        
        UserOrder::where('order_no', '=', $order_no)->update([
            'status' => $status
        ]);

        if ($status > 1) return response([
            'status' => 429,
            'message' => 'Order paid unsuccessfully'
        ], 429);

        return response([
            'status' => 200,
            'message' => 'Order paid successfully'
        ]);
    }
}