{{-- Shared form for create & edit. Expects: $formAction, $method ('POST'|'PUT'), $initData (array),
     $countries, $currencies, $incoterms, $buyers --}}
<div x-data="quotationForm(@js($initData), @js(collect($currencies)->map(fn($c) => $c['symbol'])))">
    <form method="POST" action="{{ $formAction }}">
        @csrf
        @if($method === 'PUT')
            @method('PUT')
        @endif

        <!-- Buyer -->
        <div class="card bg-base-100 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="card-title text-base mb-4">Buyer Information</h2>

                <div class="form-control mb-3 relative">
                    <label class="label py-1"><span class="label-text text-xs">Search Existing Buyer</span></label>
                    <input type="text" x-model="buyer_search" @input.debounce.300ms="searchBuyers()"
                        placeholder="Type buyer company name..." class="input input-bordered input-sm" autocomplete="off">
                    <div x-show="buyer_results.length" @click.outside="buyer_results = []"
                        class="absolute z-10 top-full mt-1 w-full bg-white border rounded-lg shadow-lg max-h-56 overflow-y-auto">
                        <template x-for="b in buyer_results" :key="b.id">
                            <div @click="selectBuyer(b)" class="px-3 py-2 hover:bg-base-200 cursor-pointer text-sm border-b last:border-0">
                                <div class="font-semibold" x-text="b.company_name"></div>
                                <div class="text-xs text-base-content/60" x-text="[b.city, b.country].filter(Boolean).join(', ')"></div>
                            </div>
                        </template>
                    </div>
                    <div class="mt-1" x-show="buyer_id">
                        <button type="button" @click="clearBuyer()" class="text-xs text-error underline">Clear selected buyer (enter manually)</button>
                    </div>
                </div>

                <input type="hidden" name="buyer_id" :value="buyer_id">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Buyer / Company Name *</span></label>
                        <input type="text" name="buyer_company_name" x-model="buyer.company_name" class="input input-bordered input-sm" required>
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Contact Person</span></label>
                        <input type="text" name="buyer_contact_person" x-model="buyer.contact_person" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Email</span></label>
                        <input type="email" name="buyer_email" x-model="buyer.email" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Phone</span></label>
                        <input type="text" name="buyer_phone" x-model="buyer.phone" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control sm:col-span-2">
                        <label class="label py-1"><span class="label-text text-xs">Address</span></label>
                        <textarea name="buyer_address" x-model="buyer.address" class="textarea textarea-bordered textarea-sm" rows="2"></textarea>
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">City</span></label>
                        <input type="text" name="buyer_city" x-model="buyer.city" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">State / Province</span></label>
                        <input type="text" name="buyer_state_province" x-model="buyer.state_province" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Postal Code</span></label>
                        <input type="text" name="buyer_postal_code" x-model="buyer.postal_code" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Country</span></label>
                        <select name="buyer_country_code" x-model="buyer.country_code" class="select select-bordered select-sm">
                            <option value="">— Select Country —</option>
                            @foreach($countries as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control sm:col-span-2">
                        <label class="label py-1"><span class="label-text text-xs">Tax / VAT / EORI / Company Registration No. (optional)</span></label>
                        <input type="text" name="buyer_tax_vat_number" x-model="buyer.tax_vat_number" class="input input-bordered input-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Trade Terms -->
        <div class="card bg-base-100 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="card-title text-base mb-4">Trade Terms</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Quotation Date *</span></label>
                        <input type="date" name="quotation_date" x-model="quotation_date" class="input input-bordered input-sm" required>
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Validity (days) *</span></label>
                        <select name="validity_days" x-model.number="validity_days" class="select select-bordered select-sm">
                            <option value="7">7 days</option>
                            <option value="14">14 days</option>
                            <option value="30">30 days</option>
                            <option value="60">60 days</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Valid Until (calculated)</span></label>
                        <div class="input input-bordered input-sm bg-base-200 flex items-center text-sm" x-text="validUntil()"></div>
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Currency *</span></label>
                        <select name="currency" x-model="currency" @change="calcTotal()" class="select select-bordered select-sm" required>
                            @foreach($currencies as $code => $c)
                                <option value="{{ $code }}">{{ $code }} — {{ $c['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Incoterm 2020 *</span></label>
                        <select name="incoterm" x-model="incoterm" class="select select-bordered select-sm" required>
                            @foreach($incoterms as $term)
                                <option value="{{ $term }}">{{ $term }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Named Place / Port</span></label>
                        <input type="text" name="incoterm_place" x-model="incoterm_place" placeholder="e.g. Surabaya Port, Indonesia" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control sm:col-span-3">
                        <div class="text-xs px-1 py-1 rounded bg-base-200 inline-block" x-show="incoterm && incoterm_place" x-text="incoterm + ' ' + incoterm_place"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping (optional) -->
        <div class="card bg-base-100 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="card-title text-base mb-4">Shipping Information <span class="text-xs font-normal text-base-content/50">(optional)</span></h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Destination Country</span></label>
                        <select name="destination_country_code" x-model="destination_country_code" class="select select-bordered select-sm">
                            <option value="">— Select Country —</option>
                            @foreach($countries as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Port of Loading</span></label>
                        <input type="text" name="port_of_loading" x-model="port_of_loading" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Port of Discharge</span></label>
                        <input type="text" name="port_of_discharge" x-model="port_of_discharge" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Final Destination</span></label>
                        <input type="text" name="final_destination" x-model="final_destination" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Shipping Method</span></label>
                        <input type="text" name="shipping_method" x-model="shipping_method" placeholder="e.g. Sea Freight (FCL)" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Production Lead Time</span></label>
                        <input type="text" name="production_lead_time" x-model="production_lead_time" placeholder="e.g. 14-21 working days" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Estimated Shipment</span></label>
                        <input type="text" name="estimated_shipment" x-model="estimated_shipment" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">MOQ</span></label>
                        <input type="text" name="moq" x-model="moq" placeholder="e.g. 100 KG" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Country of Origin</span></label>
                        <input type="text" name="country_of_origin" x-model="country_of_origin" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control sm:col-span-3">
                        <label class="label py-1"><span class="label-text text-xs">Payment Terms</span></label>
                        <textarea name="payment_terms" x-model="payment_terms" placeholder="e.g. 30% advance payment, 70% before shipment" class="textarea textarea-bordered textarea-sm" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="card bg-base-100 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="card-title text-base mb-4">Items</h2>

                <template x-for="(item, index) in items" :key="index">
                    <div class="border rounded-lg p-3 mb-3 relative">
                        <div class="flex gap-1 absolute top-2 right-2">
                            <button type="button" @click="duplicateItem(index)" title="Duplicate"
                                class="btn btn-ghost btn-xs btn-circle">⧉</button>
                            <button type="button" @click="removeItem(index)" x-show="items.length > 1" title="Remove"
                                class="btn btn-ghost btn-xs btn-circle text-error">✕</button>
                        </div>

                        <div class="form-control mb-3 relative sm:w-2/3">
                            <label class="label py-1"><span class="label-text text-xs">Product *</span></label>
                            <input type="text" x-model="item._search" @input.debounce.300ms="searchProducts(index)"
                                @focus="productSearchIndex = index" placeholder="Search product master or type freely..."
                                class="input input-bordered input-sm" autocomplete="off">
                            <div x-show="productSearchIndex === index && productResults.length"
                                @click.outside="productResults = []"
                                class="absolute z-10 top-full mt-1 w-full bg-white border rounded-lg shadow-lg max-h-56 overflow-y-auto">
                                <template x-for="p in productResults" :key="p.id">
                                    <div @click="selectProduct(index, p)" class="px-3 py-2 hover:bg-base-200 cursor-pointer text-sm border-b last:border-0">
                                        <div class="font-semibold" x-text="p.name"></div>
                                        <div class="text-xs text-base-content/60" x-text="p.species || ''"></div>
                                    </div>
                                </template>
                            </div>
                            <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                            <input type="hidden" :name="`items[${index}][product_name]`" x-model="item.product_name">
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 mb-2">
                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs">Species</span></label>
                                <input type="text" :name="`items[${index}][species]`" x-model="item.species" class="input input-bordered input-xs">
                            </div>
                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs">Grade</span></label>
                                <input type="text" :name="`items[${index}][grade]`" x-model="item.grade" class="input input-bordered input-xs">
                            </div>
                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs">Size</span></label>
                                <input type="text" :name="`items[${index}][size]`" x-model="item.size" class="input input-bordered input-xs">
                            </div>
                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs">Weight</span></label>
                                <input type="text" :name="`items[${index}][weight]`" x-model="item.weight" class="input input-bordered input-xs">
                            </div>
                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs">Moisture</span></label>
                                <input type="text" :name="`items[${index}][moisture]`" x-model="item.moisture" class="input input-bordered input-xs">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 mb-2">
                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs">Packaging</span></label>
                                <input type="text" :name="`items[${index}][packaging]`" x-model="item.packaging" class="input input-bordered input-xs">
                            </div>
                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs">Condition</span></label>
                                <input type="text" :name="`items[${index}][condition]`" x-model="item.condition" class="input input-bordered input-xs">
                            </div>
                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs">Aroma</span></label>
                                <input type="text" :name="`items[${index}][aroma]`" x-model="item.aroma" class="input input-bordered input-xs">
                            </div>
                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs">HS Code</span></label>
                                <input type="text" :name="`items[${index}][hs_code]`" x-model="item.hs_code" class="input input-bordered input-xs">
                            </div>
                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs">Country of Origin</span></label>
                                <input type="text" :name="`items[${index}][country_of_origin]`" x-model="item.country_of_origin" class="input input-bordered input-xs">
                            </div>
                        </div>

                        <div class="form-control mb-2">
                            <label class="label py-0.5"><span class="label-text text-xs">Description / Notes</span></label>
                            <input type="text" :name="`items[${index}][description]`" x-model="item.description" class="input input-bordered input-sm">
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs">Quantity *</span></label>
                                <input type="number" step="0.01" min="0.01" :name="`items[${index}][quantity]`"
                                    x-model.number="item.quantity" @input="calcTotal()" class="input input-bordered input-sm" required>
                            </div>
                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs">Unit</span></label>
                                <input type="text" :name="`items[${index}][unit]`" x-model="item.unit" placeholder="KG" class="input input-bordered input-sm">
                            </div>
                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs">Unit Price ({{ '' }} <span x-text="currency"></span>) *</span></label>
                                <input type="number" step="0.01" min="0" :name="`items[${index}][unit_price]`"
                                    x-model.number="item.unit_price" @input="calcTotal()" class="input input-bordered input-sm" required>
                            </div>
                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs">Line Total</span></label>
                                <div class="input input-bordered input-sm bg-base-200 flex items-center text-sm font-semibold">
                                    <span x-text="currency"></span>&nbsp;<span x-text="fmt((item.quantity||0) * (item.unit_price||0))"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <button type="button" @click="addItem()" class="btn btn-outline btn-sm w-full">+ Add Item</button>
            </div>
        </div>

        <!-- Totals -->
        <div class="card bg-base-100 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="card-title text-base mb-4">Pricing & Charges</h2>
                <p class="text-xs text-base-content/50 mb-3">Freight, insurance, and tax are optional — do not assume they apply for every Incoterm.</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Discount</span></label>
                        <input type="number" step="0.01" min="0" name="discount_amount" x-model.number="discount_amount" @input="calcTotal()" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Freight (optional)</span></label>
                        <input type="number" step="0.01" min="0" name="freight_amount" x-model.number="freight_amount" @input="calcTotal()" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Insurance (optional)</span></label>
                        <input type="number" step="0.01" min="0" name="insurance_amount" x-model.number="insurance_amount" @input="calcTotal()" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Other Charges — Label</span></label>
                        <input type="text" name="other_charges_label" x-model="other_charges_label" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Other Charges — Amount</span></label>
                        <input type="number" step="0.01" min="0" name="other_charges_amount" x-model.number="other_charges_amount" @input="calcTotal()" class="input input-bordered input-sm">
                    </div>
                    <div></div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Tax Label (optional)</span></label>
                        <input type="text" name="tax_label" x-model="tax_label" placeholder="e.g. VAT" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Tax Rate % (optional)</span></label>
                        <input type="number" step="0.01" min="0" max="100" name="tax_rate" x-model.number="tax_rate" @input="calcTotal()" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Tax Amount</span></label>
                        <input type="number" step="0.01" min="0" name="tax_amount" x-model.number="tax_amount" @input="calcTotal()" class="input input-bordered input-sm">
                    </div>
                </div>

                <div class="form-control mb-4">
                    <label class="label py-1"><span class="label-text text-xs">Notes</span></label>
                    <textarea name="notes" x-model="notes" class="textarea textarea-bordered textarea-sm" rows="2"></textarea>
                </div>

                <div class="divider"></div>
                <div class="flex flex-col items-end gap-1 text-sm">
                    <div class="flex gap-4 justify-between w-64">
                        <span>Subtotal:</span>
                        <span><span x-text="currency"></span> <span x-text="fmt(subtotal)"></span></span>
                    </div>
                    <div class="flex gap-4 justify-between w-64 text-success" x-show="discount_amount > 0">
                        <span>Discount:</span>
                        <span>- <span x-text="currency"></span> <span x-text="fmt(discount_amount)"></span></span>
                    </div>
                    <div class="flex gap-4 justify-between w-64" x-show="freight_amount > 0">
                        <span>Freight:</span>
                        <span>+ <span x-text="currency"></span> <span x-text="fmt(freight_amount)"></span></span>
                    </div>
                    <div class="flex gap-4 justify-between w-64" x-show="insurance_amount > 0">
                        <span>Insurance:</span>
                        <span>+ <span x-text="currency"></span> <span x-text="fmt(insurance_amount)"></span></span>
                    </div>
                    <div class="flex gap-4 justify-between w-64" x-show="other_charges_amount > 0">
                        <span x-text="other_charges_label || 'Other Charges'"></span>
                        <span>+ <span x-text="currency"></span> <span x-text="fmt(other_charges_amount)"></span></span>
                    </div>
                    <div class="flex gap-4 justify-between w-64" x-show="tax_amount > 0">
                        <span x-text="tax_label || 'Tax'"></span>
                        <span>+ <span x-text="currency"></span> <span x-text="fmt(tax_amount)"></span></span>
                    </div>
                    <div class="flex gap-4 justify-between w-64 font-bold text-base border-t pt-1 mt-1">
                        <span>Grand Total:</span>
                        <span><span x-text="currency"></span> <span x-text="fmt(grandTotal)"></span></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('admin.quotations.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Quotation</button>
        </div>
    </form>

    <!-- Live Preview -->
    <div class="card bg-base-100 shadow-sm mt-6">
        <div class="card-body p-4">
            <h2 class="card-title text-base mb-4">Live Preview</h2>
            <div class="border rounded-lg p-4 text-sm bg-white">
                <div class="flex justify-between mb-3">
                    <div>
                        <div class="font-bold" x-text="buyer.company_name || '(Buyer name)'"></div>
                        <div class="text-xs text-base-content/60" x-text="buyer.contact_person"></div>
                        <div class="text-xs text-base-content/60" x-text="[buyer.city, buyer.state_province, buyer.country_code].filter(Boolean).join(', ')"></div>
                    </div>
                    <div class="text-right text-xs">
                        <div>Date: <span x-text="quotation_date"></span></div>
                        <div>Valid Until: <span x-text="validUntil()"></span></div>
                        <div><span x-text="incoterm"></span> <span x-text="incoterm_place"></span></div>
                    </div>
                </div>
                <table class="table table-xs w-full mb-3">
                    <thead>
                        <tr><th>Product</th><th>Spec</th><th class="text-right">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Total</th></tr>
                    </thead>
                    <tbody>
                        <template x-for="item in items" :key="item.product_name + Math.random()">
                            <tr>
                                <td x-text="item.product_name || '-'"></td>
                                <td class="text-xs text-base-content/60" x-text="[item.grade, item.size].filter(Boolean).join(' / ')"></td>
                                <td class="text-right" x-text="(item.quantity||0) + ' ' + (item.unit||'')"></td>
                                <td class="text-right" x-text="fmt(item.unit_price)"></td>
                                <td class="text-right font-semibold" x-text="fmt((item.quantity||0)*(item.unit_price||0))"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div class="flex justify-end">
                    <div class="text-right">
                        <div class="text-xs text-base-content/60">Subtotal: <span x-text="currency"></span> <span x-text="fmt(subtotal)"></span></div>
                        <div class="font-bold">Grand Total: <span x-text="currency"></span> <span x-text="fmt(grandTotal)"></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function quotationForm(init, currencySymbols) {
    return {
        buyer_id: init.buyer_id || null,
        buyer_search: '',
        buyer_results: [],
        buyer: {
            company_name: init.buyer_company_name || '',
            contact_person: init.buyer_contact_person || '',
            email: init.buyer_email || '',
            phone: init.buyer_phone || '',
            address: init.buyer_address || '',
            city: init.buyer_city || '',
            state_province: init.buyer_state_province || '',
            postal_code: init.buyer_postal_code || '',
            country_code: init.buyer_country_code || '',
            tax_vat_number: init.buyer_tax_vat_number || '',
        },
        quotation_date: init.quotation_date || new Date().toISOString().slice(0, 10),
        validity_days: init.validity_days || 14,
        currency: init.currency || 'USD',
        incoterm: init.incoterm || 'FOB',
        incoterm_place: init.incoterm_place || '',
        destination_country_code: init.destination_country_code || '',
        port_of_loading: init.port_of_loading || '',
        port_of_discharge: init.port_of_discharge || '',
        final_destination: init.final_destination || '',
        shipping_method: init.shipping_method || '',
        production_lead_time: init.production_lead_time || '',
        estimated_shipment: init.estimated_shipment || '',
        payment_terms: init.payment_terms || '',
        moq: init.moq || '',
        country_of_origin: init.country_of_origin || 'Indonesia',
        discount_amount: init.discount_amount || 0,
        freight_amount: init.freight_amount || 0,
        insurance_amount: init.insurance_amount || 0,
        other_charges_label: init.other_charges_label || '',
        other_charges_amount: init.other_charges_amount || 0,
        tax_label: init.tax_label || '',
        tax_rate: init.tax_rate || 0,
        tax_amount: init.tax_amount || 0,
        notes: init.notes || '',
        items: (init.items && init.items.length) ? init.items.map(i => ({ _search: i.product_name, ...i })) : [],
        productSearchIndex: null,
        productResults: [],
        subtotal: 0,
        grandTotal: 0,

        init() {
            if (this.items.length === 0) this.addItem();
            this.calcTotal();
        },
        blankItem() {
            return {
                _search: '', product_id: null, product_name: '', species: '', grade: '', size: '',
                weight: '', moisture: '', packaging: '', condition: '', aroma: '', description: '',
                hs_code: '', country_of_origin: '', quantity: 1, unit: 'KG', unit_price: 0,
            };
        },
        addItem() { this.items.push(this.blankItem()); },
        removeItem(i) { if (this.items.length > 1) { this.items.splice(i, 1); this.calcTotal(); } },
        duplicateItem(i) { this.items.splice(i + 1, 0, JSON.parse(JSON.stringify(this.items[i]))); this.calcTotal(); },

        async searchBuyers() {
            if (this.buyer_search.length < 2) { this.buyer_results = []; return; }
            const res = await fetch('{{ route("admin.buyers.search") }}?q=' + encodeURIComponent(this.buyer_search));
            this.buyer_results = await res.json();
        },
        selectBuyer(b) {
            this.buyer_id = b.id;
            this.buyer = {
                company_name: b.company_name, contact_person: b.contact_person || '', email: b.email || '',
                phone: b.phone || '', address: b.address || '', city: b.city || '',
                state_province: b.state_province || '', postal_code: b.postal_code || '',
                country_code: b.country_code || '', tax_vat_number: b.tax_vat_number || '',
            };
            this.buyer_results = [];
            this.buyer_search = '';
        },
        clearBuyer() {
            this.buyer_id = null;
            this.buyer = { company_name: '', contact_person: '', email: '', phone: '', address: '', city: '', state_province: '', postal_code: '', country_code: '', tax_vat_number: '' };
        },

        async searchProducts(index) {
            this.productSearchIndex = index;
            const q = this.items[index]._search || '';
            if (q.length < 1) { this.productResults = []; return; }
            const res = await fetch('{{ route("admin.products.search") }}?q=' + encodeURIComponent(q));
            this.productResults = await res.json();
        },
        selectProduct(index, p) {
            const item = this.items[index];
            item.product_id = p.id;
            item.product_name = p.name;
            item._search = p.name;
            item.species = p.species || '';
            item.grade = (p.attributes && p.attributes.grade) || '';
            item.size = (p.attributes && p.attributes.size) || '';
            item.weight = (p.attributes && p.attributes.weight) || '';
            item.moisture = (p.attributes && p.attributes.moisture) || '';
            item.packaging = (p.attributes && p.attributes.packaging) || '';
            item.condition = (p.attributes && p.attributes.condition) || '';
            item.aroma = (p.attributes && p.attributes.aroma) || '';
            item.description = (p.attributes && p.attributes.description) || '';
            item.hs_code = p.hs_code || '';
            item.country_of_origin = p.default_country_of_origin || '';
            item.unit = p.default_unit || 'KG';
            this.productResults = [];
            this.productSearchIndex = null;
        },

        calcTotal() {
            this.subtotal = this.items.reduce((s, i) => s + (Number(i.quantity) || 0) * (Number(i.unit_price) || 0), 0);
            if (this.tax_rate > 0) {
                this.tax_amount = Math.round(this.subtotal * this.tax_rate) / 100;
            }
            this.grandTotal = this.subtotal
                - Number(this.discount_amount || 0)
                + Number(this.freight_amount || 0)
                + Number(this.insurance_amount || 0)
                + Number(this.other_charges_amount || 0)
                + Number(this.tax_amount || 0);
        },
        validUntil() {
            if (!this.quotation_date) return '';
            const d = new Date(this.quotation_date + 'T00:00:00');
            d.setDate(d.getDate() + Number(this.validity_days || 0));
            return d.toISOString().slice(0, 10);
        },
        fmt(n) {
            return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0);
        },
    };
}
</script>
@endpush
