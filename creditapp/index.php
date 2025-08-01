<?php
// Credit Application Form for Angel Stones
session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$status = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Credit Application - Angel Stones</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <!-- Modern styling inspired by order quote form -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
            --accent-gradient: linear-gradient(135deg, #4299e1 0%, #63b3ed 100%);
        }
        body {
            background: #f7fafc;
            font-family: 'Poppins', sans-serif;
        }
        .app-container {
            background: #fff;
            border-radius: .75rem;
            box-shadow: 0 0.25rem 1rem rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .signature-pad { border:1px solid #ced4da; border-radius:.25rem; }
        canvas { width:100%; height:200px; }
    </style>
</head>
<body>
<div class="container my-4 app-container">
    <h1 class="mb-4">Credit Application</h1>
    <?php if($status==='success'): ?>
        <div class="alert alert-success">Your application has been submitted successfully.</div>
    <?php elseif($status==='error'): ?>
        <div class="alert alert-danger">There was an error submitting the form. Please try again.</div>
    <?php endif; ?>
    <form action="submit.php" method="post" id="creditAppForm">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <h4 class="mt-4">1. Business Information</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Firm Name<span class="text-danger">*</span></label>
                <input type="text" name="firm_name" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Subsidiary Of</label>
                <input type="text" name="subsidiary_of" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Business Type</label>
                <input type="text" name="business_type" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Tax ID</label>
                <input type="text" name="tax_id" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Tax Exempt No</label>
                <input type="text" name="tax_exempt_no" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Tax Exempt State</label>
                <input type="text" name="tax_exempt_state" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Shipping Address</label>
                <textarea name="shipping_address" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Billing Address</label>
                <textarea name="billing_address" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Web</label>
                <input type="text" name="web" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Fax</label>
                <input type="text" name="fax" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Email<span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Nature of Business</label>
                <input type="text" name="nature_of_business" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Date Organized</label>
                <input type="date" name="date_organized" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">State Organized</label>
                <input type="text" name="state_organized" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Years at Address</label>
                <input type="text" name="years_at_address" class="form-control">
            </div>
        </div>

        <h4 class="mt-4">2. Corporate Officers</h4>
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label">President</label>
                <input type="text" name="officer_president" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Vice President</label>
                <input type="text" name="officer_vice_president" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Secretary</label>
                <input type="text" name="officer_secretary" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Treasurer</label>
                <input type="text" name="officer_treasurer" class="form-control">
            </div>
        </div>

        <h4 class="mt-4">3. Owners / Partners</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Owner 1 Name</label>
                <input type="text" name="owner1_name" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Owner 1 Percent</label>
                <input type="text" name="owner1_percent" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Owner 1 Address</label>
                <input type="text" name="owner1_address" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Owner 1 Res Phone</label>
                <input type="text" name="owner1_res_phone" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Owner 1 Cell</label>
                <input type="text" name="owner1_cell" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Owner 2 Name</label>
                <input type="text" name="owner2_name" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Owner 2 Percent</label>
                <input type="text" name="owner2_percent" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Owner 2 Address</label>
                <input type="text" name="owner2_address" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Owner 2 Res Phone</label>
                <input type="text" name="owner2_res_phone" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Owner 2 Cell</label>
                <input type="text" name="owner2_cell" class="form-control">
            </div>
        </div>

        <h4 class="mt-4">4. Financial / Credit References</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Reference 1 Name</label>
                <input type="text" name="fin1_name" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Reference 1 Phone</label>
                <input type="text" name="fin1_phone" class="form-control">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Acct #</label>
                <input type="text" name="fin1_acct" class="form-control">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Reference 1 Address</label>
                <input type="text" name="fin1_address" class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Reference 2 Name</label>
                <input type="text" name="fin2_name" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Reference 2 Phone</label>
                <input type="text" name="fin2_phone" class="form-control">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Acct #</label>
                <input type="text" name="fin2_acct" class="form-control">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Reference 2 Address</label>
                <input type="text" name="fin2_address" class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Reference 3 Name</label>
                <input type="text" name="fin3_name" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Reference 3 Phone</label>
                <input type="text" name="fin3_phone" class="form-control">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Acct #</label>
                <input type="text" name="fin3_acct" class="form-control">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Reference 3 Address</label>
                <input type="text" name="fin3_address" class="form-control">
            </div>
        </div>

        <h4 class="mt-4">5. Lending Institution & Creditor</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Lender Name</label>
                <input type="text" name="lender_name" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Lender Phone</label>
                <input type="text" name="lender_phone" class="form-control">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Lender Address</label>
                <input type="text" name="lender_address" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Lender Acct #</label>
                <input type="text" name="lender_acct" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Creditor Name</label>
                <input type="text" name="creditor_name" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Creditor Phone</label>
                <input type="text" name="creditor_phone" class="form-control">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Creditor Address</label>
                <input type="text" name="creditor_address" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Creditor Acct #</label>
                <input type="text" name="creditor_acct" class="form-control">
            </div>
        </div>

        <h4 class="mt-4">6. Trade References</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Trade 1 Name</label>
                <input type="text" name="trade1_name" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Trade 1 Contact</label>
                <input type="text" name="trade1_contact" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Trade 2 Name</label>
                <input type="text" name="trade2_name" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Trade 2 Contact</label>
                <input type="text" name="trade2_contact" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Trade 3 Name</label>
                <input type="text" name="trade3_name" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Trade 3 Contact</label>
                <input type="text" name="trade3_contact" class="form-control">
            </div>
        </div>

        <h4 class="mt-4">7. Personal Guarantee & Signature</h4>
        <p class="mb-3">
            The undersigned represents that the above stated information is correct and true to the
            best of his/her knowledge as of the date stated herein. This application is submitted to
            Angel Stones LLC for the purpose of obtaining credit with Angel Stones LLC. Upon
            acceptance of this application by Angel Stones LLC, the undersigned agrees to pay and
            abide by the terms of payment set forth and agreed upon at the time of purchase. All
            invoices must be paid within the terms specified on each sales invoice. Payments beyond
            those terms are subject to a 1.5% monthly interest charge (18% APR). Furthermore,
            applicant acknowledges and agrees to Angel Stones LLC's financial policy stating that any
            customer who knowingly defaults will be responsible for all costs of collection including
            agency commissions, attorney fees, legal filing fees, court costs and any fees associated
            with judgment execution. In consideration of credit extended by Angel Stones LLC the
            undersigned does jointly and severally personally guarantee payment of all sums due.
        </p>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Name (Guarantor 1)</label>
                <input type="text" name="sig1_name" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">SSN</label>
                <input type="text" name="sig1_ssn" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Date</label>
                <input type="date" name="sig1_date" class="form-control">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Signature<span class="text-danger">*</span></label>
                <div class="signature-pad">
                    <canvas id="sigPad1"></canvas>
                </div>
                <button type="button" class="btn btn-secondary mt-2" id="clearSig1">Clear</button>
                <input type="hidden" name="signature1_image" id="signature1_image" aria-required="true">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Name (Guarantor 2)</label>
                <input type="text" name="sig2_name" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">SSN</label>
                <input type="text" name="sig2_ssn" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Date</label>
                <input type="date" name="sig2_date" class="form-control">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Signature</label>
                <div class="signature-pad">
                    <canvas id="sigPad2"></canvas>
                </div>
                <button type="button" class="btn btn-secondary mt-2" id="clearSig2">Clear</button>
                <input type="hidden" name="signature2_image" id="signature2_image">
            </div>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="1" id="agree" required aria-describedby="termsHelp">
            <label class="form-check-label" for="agree" id="termsHelp">
                I agree to the <a href="/terms-of-service.html" target="_blank">terms and conditions</a>.
            </label>
        </div>

        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

        <button type="submit" class="btn btn-primary">Submit Application</button>
    </form>
    <footer class="mt-4">
        <a href="/privacy-policy.html">Privacy Policy</a>
    </footer>
</div>

<script src="/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
    const sigPad1 = new SignaturePad(document.getElementById('sigPad1'));
    const sigPad2 = new SignaturePad(document.getElementById('sigPad2'));
    document.getElementById('clearSig1').addEventListener('click', function(){
        sigPad1.clear();
        document.getElementById('signature1_image').value = '';
    });
    document.getElementById('clearSig2').addEventListener('click', function(){
        sigPad2.clear();
        document.getElementById('signature2_image').value = '';
    });

    const form = document.getElementById('creditAppForm');
    form.addEventListener('submit', function(e){
        e.preventDefault();
        if(sigPad1.isEmpty()){
            alert('Please provide at least the first signature.');

            return false;
        }
        document.getElementById('signature1_image').value = sigPad1.toDataURL('image/png');
        if(!sigPad2.isEmpty()){
            document.getElementById('signature2_image').value = sigPad2.toDataURL('image/png');
        }

        grecaptcha.ready(function(){
            grecaptcha.execute('YOUR_SITE_KEY', {action: 'creditapp'}).then(function(token){
                document.getElementById('g-recaptcha-response').value = token;
                form.submit();
            });
        });
    });
</script>
</body>
</html>
