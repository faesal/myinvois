<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Release Notes</title>

<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #0f172a, #1e293b);
    margin:0;
    padding:40px;
    color:white;
}

/* Container */
.container {
    max-width:1000px;
    margin:auto;
}

/* Header */
h1 {
    font-size:36px;
    background: linear-gradient(90deg,#38bdf8,#a78bfa);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    margin-bottom:10px;
}

.subtitle {
    color:#cbd5f5;
    margin-bottom:20px;
}

/* Dropdown */
.select-box {
    margin-bottom:30px;
}

select {
    padding:12px 15px;
    border-radius:12px;
    border:none;
    font-size:16px;
    background:#1e293b;
    color:white;
}

/* Card */
.card {
    background: rgba(255,255,255,0.05);
    padding:20px;
    border-radius:16px;
    margin-bottom:20px;
    border:1px solid rgba(255,255,255,0.1);
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
}

/* Titles */
.card h2 {
    margin-bottom:10px;
}

/* List */
ul {
    padding-left:20px;
}

li {
    margin-bottom:5px;
}

/* Fade animation */
.version {
    animation: fade 0.4s ease;
}

@keyframes fade {
    from {opacity:0; transform: translateY(10px);}
    to {opacity:1; transform: translateY(0);}
}

/* Footer */
.footer {
    margin-top:40px;
    color:#94a3b8;
    font-size:14px;
}
</style>

</head>

<body>

<div class="container">

    <h1>🚀 Release Notes</h1>
    <p class="subtitle">Track system updates and improvements across versions.</p>

    <!-- Dropdown -->
    <div class="select-box">
        <label>Select Version:</label><br>
        <select id="versionSelect">
            <option value="v1_1">v1.1 (Latest)</option>
            <option value="v1_0">v1.0</option>
        </select>
    </div>

    <!-- ================= v1.1 ================= -->
    <div id="v1_1" class="version">

<!-- e-Invoice Core -->
<div class="card">
    <h2 style="color:#38bdf8;">📦 e-Invoice Core</h2>
    <ul>
        <li><strong>Complete e-Invoice Ecosystem</strong> — Full support for Invoice, Credit Note, Debit Note, and Refund workflows</li>
        <li><strong>Structured Data Generation</strong> — Automatic JSON and XML generation aligned with MyInvois standards</li>
        <li><strong>Advanced Validation Engine</strong> — Improved data validation to reduce rejection rates</li>
        <li><strong>Scalable Processing</strong> — Optimized handling for high-volume invoice transactions</li>
    </ul>
</div>

<!-- LHDN -->
<div class="card">
    <h2 style="color:#a78bfa;">🔄 LHDN Integration</h2>
    <ul>
        <li><strong>Direct MyInvois API Submission</strong> — Seamless connection to LHDN platform</li>
        <li><strong>Real-Time Submission Tracking</strong> — Monitor status (success, pending, rejected)</li>
        <li><strong>Improved XML Reliability</strong> — Reduced errors during submission process</li>
        <li><strong>Error Handling & Feedback</strong> — Clear response messages for debugging and retry</li>
    </ul>
</div>

<!-- Self Billing -->
<div class="card">
    <h2 style="color:#22c55e;">🧾 Self-Billing</h2>
    <ul>
        <li><strong>Self-Billed Invoice Support</strong> — Generate invoices on behalf of suppliers</li>
        <li><strong>Multi-Document Support</strong> — Credit, Debit, and Refund for self-billing</li>
        <li><strong>Unified Workflow</strong> — Fully integrated into e-Invoice lifecycle</li>
        <li><strong>Compliance Ready</strong> — Structured according to LHDN self-billing requirements</li>
    </ul>
</div>

<!-- Consolidation -->
<div class="card">
    <h2 style="color:#f59e0b;">📊 Consolidation</h2>
    <ul>
        <li><strong>Multi-Source Invoice Aggregation</strong> — Combine invoices from multiple systems</li>
        <li><strong>Improved Data Visibility</strong> — Centralized view for all consolidated records</li>
        <li><strong>Faster Processing Engine</strong> — Reduced load time for large datasets</li>
        <li><strong>Flexible Viewing Options</strong> — Enhanced listing and filtering experience</li>
    </ul>
</div>

<!-- Customer -->
<div class="card">
    <h2 style="color:#ec4899;">👥 Customer Management</h2>
    <ul>
        <li><strong>Enhanced Customer Lifecycle</strong> — Improved create, update, and management flow</li>
        <li><strong>Seamless Invoice Integration</strong> — Direct linking between customer and invoice data</li>
        <li><strong>Improved Data Accuracy</strong> — Better validation for customer information</li>
        <li><strong>Scalable Data Handling</strong> — Supports large customer datasets efficiently</li>
    </ul>
</div>

<!-- Developer -->
<div class="card">
    <h2 style="color:#06b6d4;">🧑‍💻 Developer & API</h2>
    <ul>
        <li><strong>Expanded API Coverage</strong> — Support for invoice, submission, and self-billing integration</li>
        <li><strong>General TIN Support</strong> — Added flexibility for handling multiple TIN formats</li>
        <li><strong>Cleaner API Structure</strong> — Standardized endpoints for easier integration</li>
        <li><strong>Improved Developer Experience</strong> — Better usability for API workflows</li>
    </ul>
</div>

<!-- Documentation -->
<div class="card">
    <h2 style="color:#eab308;">📚 Documentation</h2>
    <ul>
        <li><strong>Updated Integration Guides</strong> — Step-by-step API usage instructions</li>
        <li><strong>Self-Billing Documentation</strong> — Clear implementation reference</li>
        <li><strong>Improved Structure</strong> — Easier navigation and readability</li>
        <li><strong>Developer-Friendly Format</strong> — Designed for faster onboarding</li>
    </ul>
</div>

<!-- Notification -->
<div class="card">
    <h2 style="color:#ef4444;">📧 Notifications</h2>
    <ul>
        <li><strong>Subscription Alerts</strong> — Automatic expiry and status notifications</li>
        <li><strong>Credential Emails</strong> — Secure delivery of access information</li>
        <li><strong>System Feedback Notifications</strong> — Clear success and error messages</li>
    </ul>
</div>

<!-- UI -->
<div class="card">
    <h2 style="color:#8b5cf6;">🎨 User Interface Improvements</h2>
    <ul>
        <li><strong>Redesigned Dashboard Experience</strong> — Cleaner layout focused on key business insights</li>
        <li><strong>Improved Navigation Flow</strong> — Faster access to core modules</li>
        <li><strong>Enhanced Invoice Screens</strong> — Better clarity for create, listing, and submission</li>
        <li><strong>Better Form Experience</strong> — Improved validation and usability</li>
        <li><strong>Consistent Design System</strong> — Unified UI across all modules</li>
        <li><strong>Upgraded Data Tables</strong> — Easier scanning and readability</li>
        <li><strong>Responsive Layout</strong> — Optimized for desktop and mobile</li>
        <li><strong>Improved Visual Feedback</strong> — Clear loading, success, and error states</li>
    </ul>
</div>

<!-- System -->
<div class="card">
    <h2 style="color:#14b8a6;">🛠 System Improvements</h2>
    <ul>
        <li><strong>Version Upgrade</strong> — Transition from v1.0 to v1.1</li>
        <li><strong>Performance Optimization</strong> — Faster processing across modules</li>
        <li><strong>Improved Stability</strong> — Reduced system errors and better reliability</li>
        <li><strong>Codebase Refinement</strong> — Cleaner and more maintainable architecture</li>
    </ul>
</div>

</div>

    <!-- ================= v1.0 ================= -->
    <div id="v1_0" class="version" style="display:none;">

        <div class="card">
            <h2 style="color:#38bdf8;">📦 Initial Release</h2>
            <ul>
                <li>Invoice module</li>
                <li>LHDN Account management</li>
                <li>API integration</li>
            </ul>
        </div>

    </div>

    <!-- Footer -->
    <div class="footer">
        Focused on compliance, scalability, and developer experience.
    </div>

</div>

<script>
const select = document.getElementById('versionSelect');

select.addEventListener('change', function() {
    let versions = document.querySelectorAll('.version');

    versions.forEach(v => {
        v.style.display = 'none';
    });

    document.getElementById(this.value).style.display = 'block';
});
</script>

</body>
</html>