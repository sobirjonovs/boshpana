<?php

namespace Database\Seeders;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\CrmLead;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CrmLeadSeeder extends Seeder
{
    public function run(): void
    {
        $leads = [
            [
                'name' => 'Akmal Yo\'ldoshev',
                'company' => null,
                'phone' => '+998901112233',
                'telegram' => '@akmal_uy',
                'email' => null,
                'type' => LeadType::Owner,
                'status' => LeadStatus::New,
                'source' => 'olx',
                'potential_value' => 350,
                'notes' => 'Chilonzorda 2 ta kvartirasi bor, ijaraga bermoqchi.',
                'last_contacted_at' => null,
            ],
            [
                'name' => 'Dilshod Realtor',
                'company' => 'Toshkent Estate',
                'phone' => '+998902223344',
                'telegram' => '@dilshod_realtor',
                'email' => 'dilshod@toshkentestate.uz',
                'type' => LeadType::Realtor,
                'status' => LeadStatus::Contacted,
                'source' => 'telegram',
                'potential_value' => 1500,
                'notes' => 'Mustaqil makler, oyiga 10-15 ta e\'lon. Hamkorlikka qiziqdi.',
                'last_contacted_at' => Carbon::now()->subDays(2),
            ],
            [
                'name' => 'Golden House Agency',
                'company' => 'Golden House',
                'phone' => '+998903334455',
                'telegram' => '@goldenhouse_uz',
                'email' => 'info@goldenhouse.uz',
                'type' => LeadType::Agency,
                'status' => LeadStatus::Qualified,
                'source' => 'referral',
                'potential_value' => 8000,
                'notes' => 'Yirik agentlik, 200+ obyekt. CRM integratsiyasiga qiziqish bildirdi.',
                'last_contacted_at' => Carbon::now()->subDays(5),
            ],
            [
                'name' => 'Nodira Karimova',
                'company' => null,
                'phone' => '+998904445566',
                'telegram' => '@nodira_k',
                'email' => null,
                'type' => LeadType::Owner,
                'status' => LeadStatus::Won,
                'source' => 'birbir',
                'potential_value' => 500,
                'notes' => 'Yunusobodda kvartira. Premium tarifga o\'tdi.',
                'last_contacted_at' => Carbon::now()->subDays(7),
            ],
            [
                'name' => 'Bektosh Realty',
                'company' => 'Bektosh Realty',
                'phone' => '+998905556677',
                'telegram' => '@bektosh_realty',
                'email' => 'bektosh@realty.uz',
                'type' => LeadType::Agency,
                'status' => LeadStatus::Lost,
                'source' => 'cold-call',
                'potential_value' => 3000,
                'notes' => 'Hozircha o\'z tizimidan foydalanmoqda. Keyinroq qayta aloqa.',
                'last_contacted_at' => Carbon::now()->subDays(14),
            ],
            [
                'name' => 'Sherzod aka',
                'company' => null,
                'phone' => '+998906667788',
                'telegram' => '@sherzod_uy',
                'email' => null,
                'type' => LeadType::Owner,
                'status' => LeadStatus::Contacted,
                'source' => 'uybor',
                'potential_value' => 280,
                'notes' => 'Sergelida studiya. Narx bo\'yicha kelishilmoqda.',
                'last_contacted_at' => Carbon::now()->subDay(),
            ],
        ];

        foreach ($leads as $data) {
            CrmLead::firstOrCreate(
                ['telegram' => $data['telegram']],
                $data,
            );
        }
    }
}
