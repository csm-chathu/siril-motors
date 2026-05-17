<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VehicleType;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\PartCategory;
use App\Models\QualityType;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Support\Str;

class SparePartsStructureSeeder extends Seeder
{
    public function run(): void
    {
        $vehicleTypes = [
            'Car', 'Pickup', 'Truck', 'Van', 'Bus', 'Motorbike'
        ];
        foreach ($vehicleTypes as $type) {
            VehicleType::firstOrCreate(['name' => $type]);
        }

        $brands = [
            'Toyota', 'Nissan', 'Honda', 'Mitsubishi', 'Suzuki', 'Isuzu', 'Tata', 'Hyundai', 'Kia'
        ];
        foreach ($brands as $brand) {
            Brand::firstOrCreate(['name' => $brand]);
        }

        $models = [
            ['brand' => 'Toyota', 'vehicle_type' => 'Car', 'name' => 'Corolla'],
            ['brand' => 'Toyota', 'vehicle_type' => 'Pickup', 'name' => 'Hilux'],
            ['brand' => 'Nissan', 'vehicle_type' => 'Car', 'name' => 'Sunny'],
            ['brand' => 'Nissan', 'vehicle_type' => 'Van', 'name' => 'Caravan'],
            ['brand' => 'Honda', 'vehicle_type' => 'Car', 'name' => 'Civic'],
            ['brand' => 'Mitsubishi', 'vehicle_type' => 'Pickup', 'name' => 'L200'],
            ['brand' => 'Suzuki', 'vehicle_type' => 'Car', 'name' => 'Alto'],
            ['brand' => 'Isuzu', 'vehicle_type' => 'Truck', 'name' => 'NKR'],
            ['brand' => 'Tata', 'vehicle_type' => 'Truck', 'name' => 'LPT 1618'],
            ['brand' => 'Hyundai', 'vehicle_type' => 'Van', 'name' => 'H1'],
            ['brand' => 'Kia', 'vehicle_type' => 'Van', 'name' => 'Pregio'],
        ];
        foreach ($models as $m) {
            $brand = Brand::where('name', $m['brand'])->first();
            $type = VehicleType::where('name', $m['vehicle_type'])->first();
            if ($brand && $type) {
                VehicleModel::firstOrCreate([
                    'brand_id' => $brand->id,
                    'vehicle_type_id' => $type->id,
                    'name' => $m['name']
                ]);
            }
        }

        $partCategories = [
            'Engine Parts', 'Brake Parts', 'Suspension Parts', 'Electrical Parts', 'Body Parts', 'Filters', 'Cooling System', 'Transmission', 'Fuel System', 'Lights & Wipers', 'Belts & Hoses'
        ];
        foreach ($partCategories as $cat) {
            PartCategory::firstOrCreate(['name' => $cat]);
        }

        $qualityTypes = ['Genuine', 'OEM', 'Aftermarket Premium', 'Economy'];
        foreach ($qualityTypes as $q) {
            QualityType::firstOrCreate(['name' => $q]);
        }

        $suppliers = [
            ['name' => 'Bosch', 'email' => 'info@bosch.com'],
            ['name' => 'Denso', 'email' => 'info@denso.com'],
            ['name' => 'NGK', 'email' => 'info@ngk.com'],
            ['name' => 'Valeo', 'email' => 'info@valeo.com'],
            ['name' => 'Local Supplier', 'email' => 'local@supplier.com'],
        ];
        foreach ($suppliers as $sup) {
            Supplier::firstOrCreate(['email' => $sup['email']], ['name' => $sup['name'], 'is_active' => true]);
        }

        // Example products
        $products = [
            // Toyota Corolla
            ['model' => 'Corolla', 'part_category' => 'Brake Parts', 'quality' => 'Genuine', 'supplier' => 'Bosch', 'name' => 'Brake Pad', 'sku' => 'TY-COR-BRK-001-G', 'purchase_price' => 40, 'selling_price' => 65, 'stock_quantity' => 25],
            ['model' => 'Corolla', 'part_category' => 'Engine Parts', 'quality' => 'OEM', 'supplier' => 'Denso', 'name' => 'Oil Filter', 'sku' => 'TY-COR-ENG-002-O', 'purchase_price' => 12, 'selling_price' => 20, 'stock_quantity' => 40],
            // Toyota Hilux
            ['model' => 'Hilux', 'part_category' => 'Brake Parts', 'quality' => 'Economy', 'supplier' => 'Local Supplier', 'name' => 'Brake Pad', 'sku' => 'TY-HIL-BRK-001-L', 'purchase_price' => 25, 'selling_price' => 40, 'stock_quantity' => 30],
            // Nissan Sunny
            ['model' => 'Sunny', 'part_category' => 'Suspension Parts', 'quality' => 'Aftermarket Premium', 'supplier' => 'Valeo', 'name' => 'Shock Absorber', 'sku' => 'NS-SUN-SUS-001-A', 'purchase_price' => 55, 'selling_price' => 90, 'stock_quantity' => 15],
            // Isuzu NKR
            ['model' => 'NKR', 'part_category' => 'Engine Parts', 'quality' => 'Genuine', 'supplier' => 'NGK', 'name' => 'Spark Plug', 'sku' => 'IS-NKR-ENG-001-G', 'purchase_price' => 18, 'selling_price' => 30, 'stock_quantity' => 50],
        ];
        foreach ($products as $p) {
            $model = VehicleModel::where('name', $p['model'])->first();
            $cat = PartCategory::where('name', $p['part_category'])->first();
            $quality = QualityType::where('name', $p['quality'])->first();
            $supplier = Supplier::where('name', $p['supplier'])->first();
            if ($model && $cat && $quality && $supplier) {
                Product::firstOrCreate([
                    'sku' => $p['sku']
                ], [
                    'vehicle_type_id' => $model->vehicle_type_id,
                    'brand_id' => $model->brand_id,
                    'model_id' => $model->id,
                    'part_category_id' => $cat->id,
                    'quality_type_id' => $quality->id,
                    'supplier_id' => $supplier->id,
                    'name' => $p['name'],
                    'purchase_price' => $p['purchase_price'],
                    'selling_price' => $p['selling_price'],
                    'stock_quantity' => $p['stock_quantity'],
                    'min_stock_level' => 5,
                    'is_active' => true,
                ]);
            }
        }
    }
}
