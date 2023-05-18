<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $products = Product::all();

        return view('home', compact('products'));
    }

    public function buy($product_id)
    {
        $product = Product::findOrFail($product_id);

        return view('buy', compact('product'));
    }

    public function confirm(Request $request)
    {
        //find the product
        $product = Product::findOrFail($request->product_id);
        //create a user
        $user = User::firstOrCreate(
            [
                'email' => $request->email
            ], [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Str::random(10),
            'address' => $request->address,
        ]);

        auth()->login($user);

        //Add the order
        $user->orders()->create([
            'product_id' => $product->id,
            'price' => $product->price
        ]);

        //redirect to payment form
        return redirect()->route('checkout');
    }

    public function checkout()
    {
        $order = Order::query()
            ->with('product')
            ->where('user_id', auth()->user()->id)
            ->whereNull('paid_at')
            ->latest()
            ->firstOrFail();

        $paymentIntent = auth()->user()->createSetupIntent();

        return view('checkout', compact('order', 'paymentIntent'));
    }

    public function pay(Request $request)
    {
        $order = Order::where('user_id', auth()->user()->id)
            ->findOrFail($request->order_id);
        $user = auth()->user();
        try {
            $user->createOrGetStripeCustomer();
            $user->updateDefaultPaymentMethod($request->payment_method);
            $user->charge($order->price, $request->payment_method);
            $order->update(['paid_at' => now()]);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return view('success');
    }
}
