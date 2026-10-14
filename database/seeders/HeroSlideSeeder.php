<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use Illuminate\Database\Seeder;

class HeroSlideSeeder extends Seeder
{
    /**
     * Seed the homepage hero slides.
     */
    public function run(): void
    {
        HeroSlide::query()->delete();

        $slides = [
            [
                'tag' => 'EXCLUSIVE PICKS',
                'heading' => 'Everything you need, at prices you will love.',
                'subheading' => 'Shop thousands of everyday products at retail prices, or buy in bulk and save with resale pricing.',
                'cta_text' => 'Shop Now',
                'cta_link' => '/shop',
                'image_path' => 'frontend/images/2222.jpeg',
                'sort_order' => 0,
            ],
            [
                'tag' => 'WEEKLY SPECIALS',
                'heading' => 'Unbeatable deals every single week.',
                'subheading' => 'Discover hand-picked markdowns across our best-selling products. Affordable, reliable, and delivered to your door.',
                'cta_text' => 'View Deals',
                'cta_link' => '/shop',
                'image_path' => 'frontend/images/banners.png',
                'sort_order' => 1,
            ],
            [
                'tag' => 'ORDER NOW',
                'heading' => 'Buy in Bulk, Sell for Less, Earn More',
                'subheading' => 'Buy in bulk and unlock resale pricing automatically — no approval needed.',
                'cta_text' => 'Order Now',
                'cta_link' => '/shop',
                'image_path' => 'frontend/images/banners.png',
                'sort_order' => 2,
            ],
        ];

        foreach ($slides as $slide) {
            HeroSlide::create($slide);
        }
    }
}
