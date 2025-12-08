# PayMongo Frontend Integration

## Quick Integration Guide

To use PayMongo for online payments, update your checkout flow in `drinks.js`:

### Option 1: Replace Xendit with PayMongo

Find this code in `drinks.js` (around line 1052):

```javascript
// OLD CODE (Xendit)
if (typeof PaymentsAPI !== 'undefined' && PaymentsAPI && typeof PaymentsAPI.createGCash === 'function') {
    const paymentResult = await PaymentsAPI.createGCash(orderId, total);
    if (paymentResult && paymentResult.success && paymentResult.data && paymentResult.data.payment_url) {
        window.location.href = paymentResult.data.payment_url;
    }
}
```

Replace with:

```javascript
// NEW CODE (PayMongo)
if (typeof PaymentsAPI !== 'undefined' && PaymentsAPI && typeof PaymentsAPI.createPayMongo === 'function') {
    const paymentResult = await PaymentsAPI.createPayMongo(orderId, total);
    if (paymentResult && paymentResult.success && paymentResult.data) {
        // Option 1: Redirect to PayMongo checkout page
        if (paymentResult.data.checkout_url) {
            window.location.href = paymentResult.data.checkout_url;
        }
        // Option 2: Show QR code (if you want to display QR code)
        else if (paymentResult.data.payment_intent_id) {
            // Generate QR code from checkout URL or use PayMongo payment link
            showPayMongoQRCode(paymentResult.data);
        }
    }
}
```

### Option 2: Add QR Code Display

If you want to show a QR code (like your PayMongo QR), add this function:

```javascript
function showPayMongoQRCode(paymentData) {
    // Create modal with QR code
    const modal = document.createElement('div');
    modal.className = 'payment-modal';
    modal.innerHTML = `
        <div class="payment-modal-content">
            <h3>Scan to Pay</h3>
            <p>Scan this QR code with your GCash, PayMaya, or Grab Pay app</p>
            <div id="qrcode"></div>
            <p>Or <a href="${paymentData.checkout_url}" target="_blank">click here</a> to pay online</p>
            <button onclick="this.closest('.payment-modal').remove()">Close</button>
        </div>
    `;
    document.body.appendChild(modal);
    
    // Generate QR code (requires qrcode.js library)
    if (typeof QRCode !== 'undefined') {
        new QRCode(document.getElementById('qrcode'), {
            text: paymentData.checkout_url,
            width: 256,
            height: 256
        });
    } else {
        // Fallback: show link if QR library not available
        document.getElementById('qrcode').innerHTML = 
            `<a href="${paymentData.checkout_url}" target="_blank">${paymentData.checkout_url}</a>`;
    }
}
```

### Option 3: Use PayMongo JS SDK (Advanced)

For embedded payment forms, you can use PayMongo's JS SDK:

1. Add PayMongo JS SDK to your HTML:
```html
<script src="https://js.paymongo.com/v1/paymongo.js"></script>
```

2. Initialize and create payment:
```javascript
// After creating payment intent
const paymongo = PayMongo('pk_test_YOUR_PUBLIC_KEY');

paymongo.attach('#paymongo-form', {
    clientKey: paymentResult.data.client_key,
    onSuccess: function() {
        // Payment successful, redirect to thank you page
        window.location.href = 'thank-you.php?order=' + orderId;
    },
    onError: function(error) {
        console.error('Payment error:', error);
        alert('Payment failed. Please try again.');
    }
});
```

## Complete Example

Here's a complete example for the checkout flow:

```javascript
async function processPayment(orderId, total) {
    try {
        showLoading('Processing payment...');
        
        // Create PayMongo payment
        const paymentResult = await PaymentsAPI.createPayMongo(orderId, total);
        
        if (paymentResult && paymentResult.success) {
            const paymentData = paymentResult.data;
            
            // Show payment options
            showPaymentModal({
                title: 'Complete Your Payment',
                checkoutUrl: paymentData.checkout_url,
                paymentIntentId: paymentData.payment_intent_id,
                amount: paymentData.amount,
                orderId: paymentData.order_id
            });
        } else {
            throw new Error(paymentResult.message || 'Failed to create payment');
        }
    } catch (error) {
        console.error('Payment error:', error);
        alert('Payment processing failed. Please try again or contact support.');
    } finally {
        hideLoading();
    }
}

function showPaymentModal(data) {
    const modal = document.createElement('div');
    modal.className = 'payment-modal-overlay';
    modal.innerHTML = `
        <div class="payment-modal">
            <h2>Complete Payment</h2>
            <p>Order: ${data.orderId}</p>
            <p>Amount: ₱${parseFloat(data.amount).toFixed(2)}</p>
            
            <div class="payment-options">
                <div class="qr-section">
                    <h3>Scan QR Code</h3>
                    <div id="payment-qrcode"></div>
                </div>
                
                <div class="link-section">
                    <h3>Or Pay Online</h3>
                    <a href="${data.checkoutUrl}" target="_blank" class="btn-pay">
                        Pay with GCash/PayMaya/Grab Pay
                    </a>
                </div>
            </div>
            
            <button onclick="this.closest('.payment-modal-overlay').remove()">Close</button>
        </div>
    `;
    document.body.appendChild(modal);
    
    // Generate QR code
    if (typeof QRCode !== 'undefined') {
        new QRCode(document.getElementById('payment-qrcode'), {
            text: data.checkoutUrl,
            width: 200,
            height: 200
        });
    }
}
```

## Adding QR Code Library

To display QR codes, add this to your HTML:

```html
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
```

Or use CDN:
```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
```

## CSS for Payment Modal

Add this CSS for the payment modal:

```css
.payment-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
}

.payment-modal {
    background: white;
    padding: 30px;
    border-radius: 10px;
    max-width: 500px;
    text-align: center;
}

.payment-options {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.qr-section, .link-section {
    flex: 1;
}

.btn-pay {
    display: inline-block;
    padding: 12px 24px;
    background: #146B33;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-top: 10px;
}
```

## Testing

1. Use test API keys from PayMongo dashboard
2. Create a test order
3. Complete payment using test payment methods
4. Verify webhook updates order status
5. Check PayMongo dashboard for payment logs

