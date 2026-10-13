<?php

namespace Database\Seeders;

use App\Models\Drug;
use Illuminate\Database\Seeder;

/**
 * The shared drug catalog: common cardiology drugs by generic name, with the dose and directions
 * the prescription form suggests. Rows are matched by code, so it is safe to run again; edits here
 * update the shared rows, and hospitals' own drugs are never touched.
 */
class DrugCatalogSeeder extends Seeder
{
    /**
     * code => [generic name, local (Persian) name, form, strength, dose, unit, route, frequency]
     *
     * @var array<string, array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string}>
     */
    private const DRUGS = [
        'aspirin-80-tab' => ['Aspirin', 'آسپرین', 'tablet', '80 mg', '1', 'tablet', 'PO', 'QD'],
        'clopidogrel-75-tab' => ['Clopidogrel', 'کلوپیدوگرل', 'tablet', '75 mg', '1', 'tablet', 'PO', 'QD'],
        'ticagrelor-90-tab' => ['Ticagrelor', 'تیکاگرلور', 'tablet', '90 mg', '1', 'tablet', 'PO', 'BID'],
        'prasugrel-10-tab' => ['Prasugrel', 'پراسوگرل', 'tablet', '10 mg', '1', 'tablet', 'PO', 'QD'],
        'atorvastatin-20-tab' => ['Atorvastatin', 'آتورواستاتین', 'tablet', '20 mg', '1', 'tablet', 'PO', 'QHS'],
        'atorvastatin-40-tab' => ['Atorvastatin', 'آتورواستاتین', 'tablet', '40 mg', '1', 'tablet', 'PO', 'QHS'],
        'atorvastatin-80-tab' => ['Atorvastatin', 'آتورواستاتین', 'tablet', '80 mg', '1', 'tablet', 'PO', 'QHS'],
        'rosuvastatin-20-tab' => ['Rosuvastatin', 'رزوواستاتین', 'tablet', '20 mg', '1', 'tablet', 'PO', 'QD'],
        'metoprolol-succinate-47.5-tab' => ['Metoprolol succinate', 'متوپرولول سوکسینات', 'tablet', '47.5 mg', '1', 'tablet', 'PO', 'QD'],
        'metoprolol-tartrate-50-tab' => ['Metoprolol tartrate', 'متوپرولول تارتارات', 'tablet', '50 mg', '1/2', 'tablet', 'PO', 'BID'],
        'carvedilol-6.25-tab' => ['Carvedilol', 'کارودیلول', 'tablet', '6.25 mg', '1', 'tablet', 'PO', 'BID'],
        'bisoprolol-5-tab' => ['Bisoprolol', 'بیزوپرولول', 'tablet', '5 mg', '1', 'tablet', 'PO', 'QD'],
        'captopril-25-tab' => ['Captopril', 'کاپتوپریل', 'tablet', '25 mg', '1/2', 'tablet', 'PO', 'TID'],
        'enalapril-5-tab' => ['Enalapril', 'انالاپریل', 'tablet', '5 mg', '1', 'tablet', 'PO', 'BID'],
        'losartan-25-tab' => ['Losartan', 'لوزارتان', 'tablet', '25 mg', '1', 'tablet', 'PO', 'QD'],
        'valsartan-80-tab' => ['Valsartan', 'والسارتان', 'tablet', '80 mg', '1', 'tablet', 'PO', 'BID'],
        'sacubitril-valsartan-49-51-tab' => ['Sacubitril/valsartan', 'ساکوبیتریل/والسارتان', 'tablet', '49/51 mg', '1', 'tablet', 'PO', 'BID'],
        'spironolactone-25-tab' => ['Spironolactone', 'اسپیرونولاکتون', 'tablet', '25 mg', '1', 'tablet', 'PO', 'QD'],
        'furosemide-40-tab' => ['Furosemide', 'فوروزماید', 'tablet', '40 mg', '1', 'tablet', 'PO', 'QAM'],
        'furosemide-20-inj' => ['Furosemide', 'فوروزماید', 'injection', '20 mg/2 mL', '20', 'mg', 'IV', 'BID'],
        'hydrochlorothiazide-25-tab' => ['Hydrochlorothiazide', 'هیدروکلروتیازید', 'tablet', '25 mg', '1', 'tablet', 'PO', 'QD'],
        'empagliflozin-10-tab' => ['Empagliflozin', 'امپاگلیفلوزین', 'tablet', '10 mg', '1', 'tablet', 'PO', 'QD'],
        'amlodipine-5-tab' => ['Amlodipine', 'آملودیپین', 'tablet', '5 mg', '1', 'tablet', 'PO', 'QD'],
        'diltiazem-60-tab' => ['Diltiazem', 'دیلتیازم', 'tablet', '60 mg', '1', 'tablet', 'PO', 'TID'],
        'nitroglycerin-0.4-sl' => ['Nitroglycerin', 'نیتروگلیسیرین', 'sublingual tablet', '0.4 mg', '1', 'tablet', 'SL', 'PRN'],
        'isosorbide-dinitrate-10-tab' => ['Isosorbide dinitrate', 'ایزوسورباید دی نیترات', 'tablet', '10 mg', '1', 'tablet', 'PO', 'TID'],
        'warfarin-5-tab' => ['Warfarin', 'وارفارین', 'tablet', '5 mg', '1', 'tablet', 'PO', 'QD'],
        'apixaban-5-tab' => ['Apixaban', 'آپیکسابان', 'tablet', '5 mg', '1', 'tablet', 'PO', 'BID'],
        'rivaroxaban-20-tab' => ['Rivaroxaban', 'ریواروکسابان', 'tablet', '20 mg', '1', 'tablet', 'PO', 'QD'],
        'enoxaparin-40-inj' => ['Enoxaparin', 'انوکساپارین', 'injection', '40 mg/0.4 mL', '40', 'mg', 'SC', 'QD'],
        'digoxin-0.25-tab' => ['Digoxin', 'دیگوکسین', 'tablet', '0.25 mg', '1', 'tablet', 'PO', 'QD'],
        'amiodarone-200-tab' => ['Amiodarone', 'آمیودارون', 'tablet', '200 mg', '1', 'tablet', 'PO', 'QD'],
        'pantoprazole-40-tab' => ['Pantoprazole', 'پنتوپرازول', 'tablet', '40 mg', '1', 'tablet', 'PO', 'QD'],
    ];

    public function run(): void
    {
        foreach (self::DRUGS as $code => [$name, $localName, $form, $strength, $dose, $unit, $route, $frequency]) {
            Drug::withoutGlobalScopes()->updateOrCreate(['hospital_id' => null, 'code' => $code], [
                'generic_name' => $name,
                'local_name' => $localName,
                'form' => $form,
                'strength' => $strength,
                'default_dose' => $dose,
                'default_unit' => $unit,
                'default_route' => $route,
                'default_frequency' => $frequency,
            ]);
        }
    }
}
