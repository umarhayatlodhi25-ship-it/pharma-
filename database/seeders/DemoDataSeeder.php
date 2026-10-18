<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Unit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Admin User only if no users exist
        if (User::withTrashed()->count() === 0) {
            User::create([
                'name' => 'Admin User',
                'email' => 'admin@pharmacy.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]);
        }

        // 2. Standard Categories for Pharmacy & General Store
        $categories = [
            ['name' => 'Tablets & Capsules', 'slug' => 'tablets-capsules', 'description' => 'Oral solid dosages', 'product_type' => 'medicine'],
            ['name' => 'Syrups & Suspensions', 'slug' => 'syrups-suspensions', 'description' => 'Liquid oral medications', 'product_type' => 'medicine'],
            ['name' => 'Injections & Infusions', 'slug' => 'injections', 'description' => 'Intravenous & Intramuscular injections', 'product_type' => 'medicine'],
            ['name' => 'Ointments, Creams & Gels', 'slug' => 'ointments-creams', 'description' => 'Topical skin applications', 'product_type' => 'medicine'],
            ['name' => 'Antibiotics & Antivirals', 'slug' => 'antibiotics', 'description' => 'Bacterial & viral infection medicines', 'product_type' => 'medicine'],
            ['name' => 'Pain Relief & Analgesics', 'slug' => 'pain-relief', 'description' => 'Painkillers, NSAIDs and anti-inflammatory', 'product_type' => 'medicine'],
            ['name' => 'Cardiovascular & Blood Pressure', 'slug' => 'cardiovascular', 'description' => 'Heart, hypertension & cholesterol drugs', 'product_type' => 'medicine'],
            ['name' => 'Gastrointestinal & Antacids', 'slug' => 'gastrointestinal', 'description' => 'Stomach, acidity, PPIs & laxatives', 'product_type' => 'medicine'],
            ['name' => 'Respiratory & Anti-Allergy', 'slug' => 'respiratory', 'description' => 'Asthma, cough, cold & antihistamines', 'product_type' => 'medicine'],
            ['name' => 'Diabetes Care & Insulin', 'slug' => 'diabetes-care', 'description' => 'Anti-diabetic oral drugs & insulin', 'product_type' => 'medicine'],
            ['name' => 'Eye & Ear Drops', 'slug' => 'eye-ear-drops', 'description' => 'Ophthalmic and otic formulations', 'product_type' => 'medicine'],
            ['name' => 'Vitamins, Minerals & Supplements', 'slug' => 'vitamins-supplements', 'description' => 'Multivitamins, calcium & dietary supplements', 'product_type' => 'both'],
            ['name' => 'First Aid & Surgical Supplies', 'slug' => 'first-aid-surgical', 'description' => 'Bandages, cotton, syringes, surgical tape', 'product_type' => 'both'],
            ['name' => 'Baby & Mother Care', 'slug' => 'baby-care', 'description' => 'Diapers, baby formula, wipes & lotions', 'product_type' => 'general'],
            ['name' => 'Personal Care & Hygiene', 'slug' => 'personal-care', 'description' => 'Soaps, shampoos & toiletries', 'product_type' => 'general'],
            ['name' => 'Beverages & Water', 'slug' => 'beverages-water', 'description' => 'Mineral water, juices and energy drinks', 'product_type' => 'general'],
            ['name' => 'Confectionery & Snacks', 'slug' => 'snacks-confectionery', 'description' => 'Chocolates, biscuits, candies', 'product_type' => 'general'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        // 3. Standard Packaging Units
        $units = [
            ['name' => 'Tablet', 'unit_id' => 'tablet', 'symbol' => 'Tab', 'allow_decimal' => false, 'status' => 'active'],
            ['name' => 'Capsule', 'unit_id' => 'capsule', 'symbol' => 'Cap', 'allow_decimal' => false, 'status' => 'active'],
            ['name' => 'Strip', 'unit_id' => 'strip', 'symbol' => 'Str', 'allow_decimal' => false, 'status' => 'active'],
            ['name' => 'Pack', 'unit_id' => 'pack', 'symbol' => 'Pck', 'allow_decimal' => false, 'status' => 'active'],
            ['name' => 'Box', 'unit_id' => 'box', 'symbol' => 'Box', 'allow_decimal' => false, 'status' => 'active'],
            ['name' => 'Bottle', 'unit_id' => 'bottle', 'symbol' => 'Btl', 'allow_decimal' => false, 'status' => 'active'],
            ['name' => 'Piece', 'unit_id' => 'piece', 'symbol' => 'Pcs', 'allow_decimal' => false, 'status' => 'active'],
            ['name' => 'Tube', 'unit_id' => 'tube', 'symbol' => 'Tub', 'allow_decimal' => false, 'status' => 'active'],
            ['name' => 'Sachet', 'unit_id' => 'sachet', 'symbol' => 'Sch', 'allow_decimal' => false, 'status' => 'active'],
            ['name' => 'Ampoule', 'unit_id' => 'ampoule', 'symbol' => 'Amp', 'allow_decimal' => false, 'status' => 'active'],
            ['name' => 'Vial', 'unit_id' => 'vial', 'symbol' => 'Vil', 'allow_decimal' => false, 'status' => 'active'],
        ];

        foreach ($units as $u) {
            Unit::updateOrCreate(['unit_id' => $u['unit_id']], $u);
        }
    }
}
