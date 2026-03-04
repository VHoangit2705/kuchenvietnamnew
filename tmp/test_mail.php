<?php
use App\Models\products_new\ProductContentReview;
use App\Models\Kho\Product;
use App\Models\products_new\User;
use App\Mail\ContentReviewNotification;
use Illuminate\Support\Facades\Mail;

$id = 4;
$review = ProductContentReview::find($id);
if (!$review) {
    echo "Review not found\n";
    exit(1);
}

$product = Product::find($review->product_id);
if (!$product) {
    echo "Product not found\n";
    exit(1);
}

$user = User::whereNotNull('email')->first();
if (!$user) {
    echo "No user with email found\n";
    exit(1);
}

try {
    Mail::to($user->email)->send(new ContentReviewNotification($product, $review));
    echo "Mail successfully prepared and sent to " . $user->email . "\n";
} catch (\Exception $e) {
    echo "Error sending mail: " . $e->getMessage() . "\n";
}
