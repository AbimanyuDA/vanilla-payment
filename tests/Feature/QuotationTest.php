<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\CompanySettings;
use App\Models\Product;
use App\Models\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        CompanySettings::create([
            'company_name' => 'Test Export Co',
            'bank_name' => 'Test Bank',
            'bank_account_number' => '12345',
        ]);
    }

    private function baseItem(array $overrides = []): array
    {
        return array_merge([
            'product_name' => 'Vanilla Planifolia - Gourmet Premium',
            'quantity' => 100,
            'unit' => 'KG',
            'unit_price' => 20,
        ], $overrides);
    }

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'buyer_company_name' => 'Global Spice Importers GmbH',
            'quotation_date' => now()->toDateString(),
            'validity_days' => 14,
            'currency' => 'USD',
            'incoterm' => 'FOB',
            'incoterm_place' => 'Surabaya Port, Indonesia',
            'country_of_origin' => 'Indonesia',
            'items' => [$this->baseItem()],
        ], $overrides);
    }

    /** TEST 1: 1 product, 100 KG, USD 20/KG, FOB Surabaya -> subtotal USD 2,000 */
    public function test_single_item_subtotal(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.quotations.store'), $this->basePayload());

        $quotation = Quotation::first();
        $response->assertRedirect(route('admin.quotations.show', $quotation));
        $this->assertEquals(2000, $quotation->subtotal);
        $this->assertEquals(2000, $quotation->grand_total);
        $this->assertEquals('draft', $quotation->status);
    }

    /** TEST 2: multiple products, different quantities and prices */
    public function test_multiple_items_subtotal(): void
    {
        $payload = $this->basePayload([
            'items' => [
                $this->baseItem(['product_name' => 'Vanilla A', 'quantity' => 50, 'unit_price' => 25]),
                $this->baseItem(['product_name' => 'Vanilla B', 'quantity' => 30, 'unit_price' => 40]),
            ],
        ]);

        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $payload);

        $quotation = Quotation::first();
        $this->assertEquals(2450, $quotation->subtotal); // 50*25 + 30*40
        $this->assertCount(2, $quotation->items);
    }

    /** TEST 3: EUR quotation */
    public function test_eur_currency_quotation(): void
    {
        $payload = $this->basePayload(['currency' => 'EUR']);
        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $payload);

        $this->assertEquals('EUR', Quotation::first()->currency);
    }

    /** TEST 4: CIF quotation with freight + insurance */
    public function test_cif_with_freight_and_insurance(): void
    {
        $payload = $this->basePayload([
            'incoterm' => 'CIF',
            'incoterm_place' => 'Hamburg, Germany',
            'freight_amount' => 300,
            'insurance_amount' => 50,
        ]);
        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $payload);

        $quotation = Quotation::first();
        $this->assertEquals(2000, $quotation->subtotal);
        $this->assertEquals(300, $quotation->freight_amount);
        $this->assertEquals(50, $quotation->insurance_amount);
        $this->assertEquals(2350, $quotation->grand_total);
    }

    /** TEST 4b: FOB quotation must NOT auto-populate freight/insurance */
    public function test_fob_does_not_auto_apply_freight_or_insurance(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $this->basePayload());

        $quotation = Quotation::first();
        $this->assertNull($quotation->freight_amount);
        $this->assertNull($quotation->insurance_amount);
        $this->assertEquals(2000, $quotation->grand_total);
    }

    /** TEST 5: Quotation validity 14 days computes valid_until correctly */
    public function test_validity_14_days_computes_valid_until(): void
    {
        $payload = $this->basePayload(['quotation_date' => '2026-08-01', 'validity_days' => 14]);
        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $payload);

        $this->assertEquals('2026-08-15', Quotation::first()->valid_until->toDateString());
    }

    /** TEST 6: Large number of items persists correctly (multi-page PDF layout verified visually) */
    public function test_large_number_of_items_persist(): void
    {
        $items = [];
        for ($i = 1; $i <= 40; $i++) {
            $items[] = $this->baseItem(['product_name' => "Item {$i}", 'quantity' => 1, 'unit_price' => 10]);
        }
        $payload = $this->basePayload(['items' => $items]);
        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $payload);

        $quotation = Quotation::first();
        $this->assertCount(40, $quotation->items);
        $this->assertEquals(400, $quotation->subtotal);
    }

    /** TEST 7: Old quotation item snapshot stays unchanged after the product master is edited */
    public function test_quotation_item_snapshot_survives_product_master_change(): void
    {
        $product = Product::create([
            'name' => 'Vanilla Test Grade',
            'category' => 'raw',
            'default_unit' => 'KG',
            'default_country_of_origin' => 'Indonesia',
        ]);

        $payload = $this->basePayload([
            'items' => [$this->baseItem(['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 20])],
        ]);
        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $payload);

        $quotation = Quotation::first();
        $this->assertEquals(20, $quotation->items->first()->unit_price);
        $this->assertEquals('Vanilla Test Grade', $quotation->items->first()->product_name);

        $product->update(['name' => 'Vanilla Test Grade RENAMED']);

        $quotation->refresh();
        $this->assertEquals('Vanilla Test Grade', $quotation->items->first()->product_name);
    }

    /** TEST 8: Draft quotation status default */
    public function test_new_quotation_defaults_to_draft(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $this->basePayload());
        $this->assertEquals('draft', Quotation::first()->status);
    }

    /** TEST 9: Expired quotation */
    public function test_quotation_is_expired_when_valid_until_passed(): void
    {
        $payload = $this->basePayload(['quotation_date' => now()->subDays(30)->toDateString(), 'validity_days' => 14]);
        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $payload);

        $this->assertTrue(Quotation::first()->isExpired());
    }

    /** TEST 10: Quotation -> Proforma Invoice architecture is in place (status + reference column) */
    public function test_quotation_supports_converted_to_proforma_status(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $this->basePayload());
        $quotation = Quotation::first();

        $quotation->update(['status' => 'converted_to_proforma', 'converted_to_proforma_invoice_id' => 999]);
        $quotation->refresh();

        $this->assertEquals('converted_to_proforma', $quotation->status);
        $this->assertEquals(999, $quotation->converted_to_proforma_invoice_id);
    }

    /** Quotation numbering: monthly counter, prefixed with destination country code */
    public function test_quotation_number_format_includes_country_code(): void
    {
        $payload = $this->basePayload(['destination_country_code' => 'DEU']);
        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $payload);

        $this->assertMatchesRegularExpression('/^QT-DEU-\d{6}-\d{4}$/', Quotation::first()->quotation_number);
    }

    /** Falls back to the buyer's country when no explicit destination is given */
    public function test_quotation_number_falls_back_to_buyer_country(): void
    {
        $payload = $this->basePayload(['buyer_country_code' => 'SGP']);
        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $payload);

        $this->assertMatchesRegularExpression('/^QT-SGP-\d{6}-\d{4}$/', Quotation::first()->quotation_number);
    }

    /** A draft numbered before any country was known gets healed once one is set */
    public function test_placeholder_country_segment_is_healed_on_draft_update(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $this->basePayload());
        $quotation = Quotation::first();
        $this->assertStringContainsString('-XXX-', $quotation->quotation_number);

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.quotations.update', $quotation),
            $this->basePayload(['destination_country_code' => 'JPN'])
        );

        $this->assertMatchesRegularExpression('/^QT-JPN-\d{6}-\d{4}$/', $quotation->fresh()->quotation_number);
    }

    /** A quotation already issued to the buyer keeps its number, placeholder or not */
    public function test_issued_quotation_number_is_not_rewritten(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $this->basePayload());
        $quotation = Quotation::first();
        $original = $quotation->quotation_number;

        $quotation->update(['status' => 'sent']);
        $this->actingAs($this->admin, 'admin')->put(
            route('admin.quotations.update', $quotation),
            $this->basePayload(['destination_country_code' => 'JPN'])
        );

        $this->assertEquals($original, $quotation->fresh()->quotation_number);
    }

    /** The five minimum wire fields (plus charge instruction) are snapshotted onto the PDF */
    public function test_international_bank_details_are_snapshotted(): void
    {
        CompanySettings::current()->update([
            'bank_name' => 'Test Bank Name',
            'swift_bic' => 'AAAABBCCDDD',
            'bank_account_name' => 'TEST ACCOUNT HOLDER',
            'bank_account_number' => '9876543210',
            'bank_account_currency' => 'USD / IDR',
            'payment_charge_instruction' => 'OUR',
        ]);

        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $this->basePayload());
        $quotation = Quotation::first();

        $this->actingAs($this->admin, 'admin')->get(route('admin.quotations.pdf', $quotation))->assertOk();

        $snapshot = $quotation->fresh()->pdf_company_snapshot;
        $this->assertEquals('Test Bank Name', $snapshot['bank_name']);
        $this->assertEquals('AAAABBCCDDD', $snapshot['swift_bic']);
        $this->assertEquals('TEST ACCOUNT HOLDER', $snapshot['bank_account_name']);
        $this->assertEquals('9876543210', $snapshot['bank_account_number']);
        $this->assertEquals('USD / IDR', $snapshot['bank_account_currency']);
        $this->assertEquals('OUR', $snapshot['payment_charge_instruction']);
    }

    /** Optional correspondent-bank fields still round-trip when they are filled in */
    public function test_intermediary_bank_details_are_snapshotted_when_present(): void
    {
        CompanySettings::current()->update([
            'intermediary_bank_name' => 'Example Correspondent Bank N.A.',
            'intermediary_bank_swift' => 'EEEEFFGG',
        ]);

        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $this->basePayload());
        $quotation = Quotation::first();
        $this->actingAs($this->admin, 'admin')->get(route('admin.quotations.pdf', $quotation))->assertOk();

        $snapshot = $quotation->fresh()->pdf_company_snapshot;
        $this->assertEquals('Example Correspondent Bank N.A.', $snapshot['intermediary_bank_name']);
        $this->assertEquals('EEEEFFGG', $snapshot['intermediary_bank_swift']);
    }

    /** PDF generation freezes a snapshot of company/bank settings at generation time */
    public function test_pdf_generation_snapshots_company_settings(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $this->basePayload());
        $quotation = Quotation::first();
        $this->assertNull($quotation->pdf_generated_at);

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.quotations.pdf', $quotation));
        $response->assertOk();

        $quotation->refresh();
        $this->assertNotNull($quotation->pdf_generated_at);
        $this->assertEquals('Test Export Co', $quotation->pdf_company_snapshot['company_name']);

        // Changing company settings afterwards must not retroactively alter the frozen snapshot.
        CompanySettings::current()->update(['company_name' => 'Renamed Co']);
        $quotation->refresh();
        $this->assertEquals('Test Export Co', $quotation->pdf_company_snapshot['company_name']);
    }

    /** Preview streams the PDF inline and must not freeze the snapshot (previewing != issuing) */
    public function test_pdf_preview_streams_inline_without_snapshotting(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $this->basePayload());
        $quotation = Quotation::first();

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.quotations.pdf-preview', $quotation));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));

        $this->assertNull($quotation->fresh()->pdf_generated_at);
    }

    /** Existing local invoice route must remain untouched by this feature */
    public function test_existing_invoice_routes_still_work(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.invoices.index'));
        $response->assertOk();
    }

    /** All quotation/buyer/product/settings pages render without runtime (blade/view) errors */
    public function test_all_new_admin_pages_render(): void
    {
        $product = Product::create([
            'name' => 'Smoke Test Product', 'category' => 'raw',
            'default_unit' => 'KG', 'default_country_of_origin' => 'Indonesia',
        ]);
        $product->productAttributes()->create(['key' => 'grade', 'value' => 'Gourmet Premium']);

        $this->actingAs($this->admin, 'admin')->post(route('admin.buyers.store'), [
            'company_name' => 'Smoke Buyer Ltd', 'country_code' => 'DEU',
        ])->assertRedirect();
        $buyer = \App\Models\Buyer::first();

        $this->actingAs($this->admin, 'admin')->post(route('admin.quotations.store'), $this->basePayload())
            ->assertRedirect();
        $quotation = Quotation::first();

        $this->actingAs($this->admin, 'admin');
        $this->get(route('admin.quotations.index'))->assertOk();
        $this->get(route('admin.quotations.create'))->assertOk();
        $this->get(route('admin.quotations.show', $quotation))->assertOk();
        $this->get(route('admin.quotations.edit', $quotation))->assertOk();
        $this->get(route('admin.buyers.index'))->assertOk();
        $this->get(route('admin.buyers.create'))->assertOk();
        $this->get(route('admin.buyers.edit', $buyer))->assertOk();
        $this->get(route('admin.products.index'))->assertOk();
        $this->get(route('admin.products.create'))->assertOk();
        $this->get(route('admin.products.edit', $product))->assertOk();
        $this->get(route('admin.settings.company'))->assertOk();
        $this->getJson(route('admin.buyers.search', ['q' => 'Smoke']))->assertOk();
        $this->getJson(route('admin.products.search', ['q' => 'Smoke']))->assertOk()
            ->assertJsonFragment(['grade' => 'Gourmet Premium']);
    }
}
