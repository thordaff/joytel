@extends('layouts.app')

@section('title', 'Create Order - JoyTel Dashboard')
@section('page-title', 'Create New Order')

@section('page-actions')
    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Orders
    </a>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-plus-circle"></i> Create New Order
                    </h5>
                </div>
                <div class="card-body">
                    <form id="orderForm" action="{{ route('orders.store') }}" method="POST">
                        @csrf
                        
                        <!-- System Type Selection -->
                        <div class="mb-3">
                            <label class="form-label"><strong>System Type</strong></label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="system_type" id="warehouse" value="warehouse" checked onchange="toggleSystemFields()">
                                        <label class="form-check-label" for="warehouse">
                                            <strong>Warehouse API</strong>
                                            <br><small class="text-muted">For eSIM orders and OTA recharge</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="system_type" id="rsp" value="rsp" onchange="toggleSystemFields()">
                                        <label class="form-check-label" for="rsp">
                                            <strong>RSP API</strong>
                                            <br><small class="text-muted">For coupon redemption and eSIM management</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Order Type -->
                        <div class="mb-3" id="orderTypeSection">
                            <label class="form-label"><strong>Order Type</strong></label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="order_type" id="esim_order" value="esim" checked onchange="toggleOrderFields()">
                                        <label class="form-check-label" for="esim_order">
                                            <strong>eSIM Order</strong>
                                            <br><small class="text-muted">New eSIM purchase</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="order_type" id="ota_recharge" value="ota" onchange="toggleOrderFields()">
                                        <label class="form-check-label" for="ota_recharge">
                                            <strong>OTA Recharge</strong>
                                            <br><small class="text-muted">Recharge existing SIM/eSIM</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Basic Order Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product_code" class="form-label"><strong>Product Code</strong> <span class="text-danger">*</span></label>
                                    <select class="form-select" id="product_code" name="product_code" required>
                                        <option value="">Select Product...</option>
                                        <!-- Products will be loaded dynamically based on system type and order type -->
                                    </select>
                                    <div class="form-text" id="productDescription"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="quantity" class="form-label"><strong>Quantity</strong></label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="100">
                                </div>
                            </div>
                        </div>

                        <!-- eSIM Order Fields -->
                        <div id="esimFields">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="receive_name" class="form-label"><strong>Receiver Name</strong> <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="receive_name" name="receive_name" placeholder="Enter receiver name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label"><strong>Phone Number</strong> <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="+1234567890">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label"><strong>Email</strong></label>
                                        <input type="email" class="form-control" id="email" name="email" placeholder="customer@example.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="cid" class="form-label"><strong>Customer ID</strong></label>
                                        <input type="text" class="form-control" id="cid" name="cid" placeholder="Optional customer reference">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- OTA Recharge Fields -->
                        <div id="otaFields" style="display: none;">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="sn_pin" class="form-label"><strong>SN/PIN</strong> <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="sn_pin" name="sn_pin" placeholder="Enter SN/PIN of existing SIM/eSIM">
                                        <div class="form-text">The SN/PIN of the existing SIM or eSIM card to recharge</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- RSP System Fields -->
                        <div id="rspFields" style="display: none;">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>Note:</strong> RSP system orders are typically handled through coupon redemption.
                                You can create a placeholder order here and use the coupon code as SN/PIN.
                            </div>
                            
                            <div class="mb-3">
                                <label for="coupon_code" class="form-label"><strong>Coupon Code</strong></label>
                                <input type="text" class="form-control" id="coupon_code" name="coupon_code" placeholder="Enter coupon code for RSP system">
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                                <i class="bi bi-x-circle"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-check-circle"></i> Create Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Preview Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-eye"></i> Order Preview</h6>
                </div>
                <div class="card-body">
                    <div id="orderPreview" class="text-muted">
                        Fill in the form above to see order preview...
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
// Global variables for products data
let allProducts = {};
let currentProducts = [];

function toggleSystemFields() {
    const systemType = document.querySelector('input[name="system_type"]:checked').value;
    const orderTypeSection = document.getElementById('orderTypeSection');
    const esimFields = document.getElementById('esimFields');
    const rspFields = document.getElementById('rspFields');
    
    if (systemType === 'rsp') {
        orderTypeSection.style.display = 'none';
        esimFields.style.display = 'none';
        rspFields.style.display = 'block';
        
        // Clear required attributes for warehouse fields
        document.getElementById('receive_name').required = false;
        document.getElementById('phone').required = false;
    } else {
        orderTypeSection.style.display = 'block';
        rspFields.style.display = 'none';
        toggleOrderFields(); // Show appropriate fields based on order type
    }
    
    // Load products for the selected system
    loadProducts();
    updatePreview();
}

function toggleOrderFields() {
    const orderType = document.querySelector('input[name="order_type"]:checked')?.value;
    const esimFields = document.getElementById('esimFields');
    const otaFields = document.getElementById('otaFields');
    
    if (orderType === 'ota') {
        esimFields.style.display = 'none';
        otaFields.style.display = 'block';
        
        // Set required fields for OTA
        document.getElementById('receive_name').required = false;
        document.getElementById('phone').required = false;
        document.getElementById('sn_pin').required = true;
    } else {
        esimFields.style.display = 'block';
        otaFields.style.display = 'none';
        
        // Set required fields for eSIM
        document.getElementById('receive_name').required = true;
        document.getElementById('phone').required = true;
        document.getElementById('sn_pin').required = false;
    }
    
    // Load products for the selected order type
    loadProducts();
    updatePreview();
}

function loadProducts() {
    const systemType = document.querySelector('input[name="system_type"]:checked')?.value || 'warehouse';
    const orderType = document.querySelector('input[name="order_type"]:checked')?.value;
    const productSelect = document.getElementById('product_code');
    
    // Clear current options
    productSelect.innerHTML = '<option value="">Loading products...</option>';
    
    // Build URL parameters
    let url = '/api/products?system_type=' + systemType;
    if (systemType === 'warehouse' && orderType) {
        if (orderType === 'esim') {
            url += '&category=esim';
        } else if (orderType === 'ota') {
            url += '&category=recharge';
        }
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allProducts = data.data;
                populateProductSelect();
            } else {
                console.error('Error loading products:', data.message);
                productSelect.innerHTML = '<option value="">Error loading products</option>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            productSelect.innerHTML = '<option value="">Error loading products</option>';
        });
}

function populateProductSelect() {
    const productSelect = document.getElementById('product_code');
    productSelect.innerHTML = '<option value="">Select Product...</option>';
    
    // Add products grouped by category
    Object.keys(allProducts).forEach(category => {
        if (allProducts[category].length > 0) {
            const optgroup = document.createElement('optgroup');
            optgroup.label = category.charAt(0).toUpperCase() + category.slice(1) + ' Products';
            
            allProducts[category].forEach(product => {
                const option = document.createElement('option');
                option.value = product.value;
                option.textContent = product.text;
                option.setAttribute('data-price', product.price || '');
                option.setAttribute('data-description', product.description || '');
                option.setAttribute('data-region', product.region || '');
                optgroup.appendChild(option);
            });
            
            productSelect.appendChild(optgroup);
        }
    });
    
    // Add event listener for product selection
    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const description = selectedOption.getAttribute('data-description') || '';
        const price = selectedOption.getAttribute('data-price') || '';
        
        const descriptionDiv = document.getElementById('productDescription');
        if (description) {
            let text = description;
            if (price) {
                text += ` - $${price}`;
            }
            descriptionDiv.textContent = text;
        } else {
            descriptionDiv.textContent = '';
        }
        
        updatePreview();
    });
}

function updatePreview() {
    const form = document.getElementById('orderForm');
    const formData = new FormData(form);
    const preview = document.getElementById('orderPreview');
    
    let previewHTML = '<div class="row">';
    
    // System and Order Type
    previewHTML += `<div class="col-md-6"><strong>System:</strong> ${formData.get('system_type') || 'Not selected'}</div>`;
    if (formData.get('order_type')) {
        previewHTML += `<div class="col-md-6"><strong>Order Type:</strong> ${formData.get('order_type').toUpperCase()}</div>`;
    }
    
    // Product and Quantity
    previewHTML += `<div class="col-md-6"><strong>Product:</strong> ${formData.get('product_code') || 'Not selected'}</div>`;
    previewHTML += `<div class="col-md-6"><strong>Quantity:</strong> ${formData.get('quantity') || '1'}</div>`;
    
    // Conditional fields
    if (formData.get('receive_name')) {
        previewHTML += `<div class="col-md-6"><strong>Receiver:</strong> ${formData.get('receive_name')}</div>`;
    }
    if (formData.get('phone')) {
        previewHTML += `<div class="col-md-6"><strong>Phone:</strong> ${formData.get('phone')}</div>`;
    }
    if (formData.get('email')) {
        previewHTML += `<div class="col-md-6"><strong>Email:</strong> ${formData.get('email')}</div>`;
    }
    if (formData.get('sn_pin')) {
        previewHTML += `<div class="col-md-6"><strong>SN/PIN:</strong> ${formData.get('sn_pin')}</div>`;
    }
    if (formData.get('coupon_code')) {
        previewHTML += `<div class="col-md-6"><strong>Coupon:</strong> ${formData.get('coupon_code')}</div>`;
    }
    
    previewHTML += '</div>';
    preview.innerHTML = previewHTML;
}

// Form submission
document.getElementById('orderForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-spinner-border"></i> Creating Order...';
    
    const formData = new FormData(this);
    
    // Handle different order types
    const systemType = formData.get('system_type');
    const orderType = formData.get('order_type');
    
    if (systemType === 'warehouse' && orderType === 'ota') {
        // For OTA recharge, use different endpoint
        fetch('/api/orders/ota-recharge', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_code: formData.get('product_code'),
                sn_pin: formData.get('sn_pin'),
                quantity: parseInt(formData.get('quantity'))
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = `/orders/${data.data.order_tid}`;
            } else {
                alert('Error creating order: ' + (data.message || 'Unknown error'));
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Create Order';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while creating the order.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Create Order';
        });
    } else {
        // For regular orders
        fetch('/api/orders', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(Object.fromEntries(formData))
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = `/orders/${data.data.order_tid}`;
            } else {
                alert('Error creating order: ' + (data.message || 'Unknown error'));
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Create Order';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while creating the order.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Create Order';
        });
    }
});

// Add event listeners for preview updates
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('#orderForm input, #orderForm select');
    inputs.forEach(input => {
        input.addEventListener('change', updatePreview);
        input.addEventListener('input', updatePreview);
    });
    
    // Load initial products
    loadProducts();
    
    // Initial preview
    updatePreview();
});
</script>
@endpush