<?php
// ---------------------------------------------------------------------------
// Database connection (local MariaDB via mysqli)
// ---------------------------------------------------------------------------
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "birthday_wishnote";

// Report errors as return values instead of exceptions so we can show
// friendly messages in the page instead of a fatal error.
mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli($servername, $username, $password, $dbname);

$successMessage = "";
$errorMessage   = "";

if ($conn->connect_error) {
    $errorMessage = "Database connection failed. Please check that MariaDB is running.";
}

// ---------------------------------------------------------------------------
// Handle the POST request
// ---------------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && !$conn->connect_error) {

    // Capture all four variables from the form.
    $FirstName   = trim($_POST["FirstName"]   ?? "");
    $LastName    = trim($_POST["LastName"]    ?? "");
    $GovPosition = trim($_POST["GovPosition"] ?? "");
    $Location    = trim($_POST["Location"]    ?? "");   // captured, but never saved (see flaw below)

    // =======================================================================
    // INTENTIONAL FLAW (for testing / debugging exercises):
    //
    // The form collects FOUR fields, and $Location is captured above, but the
    // INSERT statement below deliberately OMITS the Location column. It is
    // missing from:
    //   1. the column list in the SQL string, and
    //   2. the bind_param() call (type string "sss" instead of "ssss",
    //      and no $Location argument).
    //
    // Result: only FirstName, LastName and GovPosition are stored.
    // The Location value the user typed (and confirmed in the modal) is
    // silently dropped, so the database never receives it.
    // To fix: add `Location` to the column list, add a fourth `?`,
    // change "sss" to "ssss", and pass $Location to bind_param().
    // =======================================================================
    $sql  = "INSERT INTO main_corrupt_politicians (FirstName, LastName, GovPosition) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        // Fallback error if the query fails to prepare
        $errorMessage = "Something went wrong while preparing the request. Please try again later.";
    } else {
        $stmt->bind_param("sss", $FirstName, $LastName, $GovPosition); // <-- no $Location (the flaw)

        if ($stmt->execute()) {
            $successMessage = "Success! The wish note has been sent.";
        } else {
            $errorMessage = "The wish note could not be saved. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kara David's Wish Note</title>
    <style>
        :root {
            --blue:       #1e6fd9;
            --blue-dark:  #1858b0;
            --blue-light: #e8f1fc;
            --gray-bg:    #f0f2f5;
            --gray-line:  #d5dbe3;
            --text:       #1f2933;
            --muted:      #5f6b7a;
            --danger:     #c0392b;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background: var(--gray-bg);
            color: var(--text);
            font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        .card {
            width: 100%;
            max-width: 460px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: var(--blue);
            padding: 20px 28px;
        }

        .card-header h2 {
            margin: 0;
            color: #fff;
            font-size: 1.35rem;
            font-weight: 600;
        }

        .card-body { padding: 28px; }

        .message {
            padding: 12px 14px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            border-left: 4px solid;
        }
        .message.success { background: #e6f6ec; border-color: #2e9e5b; color: #1d6b3c; }
        .message.error   { background: #fdecea; border-color: var(--danger); color: #8f2a1f; }

        .field { margin-bottom: 18px; }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .optional { font-weight: 400; color: var(--muted); }

        input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            font-size: 1rem;
            font-family: inherit;
            color: var(--text);
            border: 1px solid var(--gray-line);
            border-radius: 4px;
            background: #fff;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(30, 111, 217, 0.2);
        }

        button {
            font-family: inherit;
            font-size: 1rem;
            font-weight: 600;
            padding: 11px 18px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.15s;
        }
        button:focus-visible { outline: 3px solid rgba(30, 111, 217, 0.4); outline-offset: 2px; }

        .btn-primary   { background: var(--blue); color: #fff; }
        .btn-primary:hover { background: var(--blue-dark); }
        .btn-secondary { background: var(--gray-bg); color: var(--text); }
        .btn-secondary:hover { background: var(--gray-line); }

        #submitBtn { width: 100%; margin-top: 4px; }

        /* ---------- Modal ---------- */
        .modal-overlay {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: rgba(20, 30, 45, 0.55);
            z-index: 100;
        }
        .modal-overlay.open { display: flex; }

        .modal {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 10px 32px rgba(0, 0, 0, 0.25);
            padding: 24px;
        }
        .modal h3 { margin: 0 0 16px; color: var(--blue); font-size: 1.2rem; }

        .summary {
            background: var(--blue-light);
            border-radius: 4px;
            padding: 12px 14px;
            margin-bottom: 20px;
        }
        .summary-row { padding: 6px 0; font-size: 0.95rem; word-break: break-word; }
        .summary-row + .summary-row { border-top: 1px solid #c9dcf4; }
        .summary-label { display: block; font-size: 0.8rem; color: var(--muted); }

        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap; }

        @media (max-width: 480px) {
            .card-body { padding: 20px; }
            .modal-actions button { flex: 1; }
        }
    </style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <h2>Kara David's Wish Note</h2>
    </div>

    <div class="card-body">
        <?php if ($successMessage): ?>
            <div class="message success"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="message error"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <form id="wishForm" method="POST" action="">
            <div class="field">
                <label for="FirstName">Target's First Name</label>
                <input type="text" id="FirstName" name="FirstName" maxlength="30" required>
            </div>

            <div class="field">
                <label for="LastName">Target's Last Name</label>
                <input type="text" id="LastName" name="LastName" maxlength="30" required>
            </div>

            <div class="field">
                <label for="GovPosition">Target's Government Position</label>
                <input type="text" id="GovPosition" name="GovPosition" maxlength="20" required>
            </div>

            <div class="field">
                <label for="Location">Target's Location <span class="optional">(optional)</span></label>
                <input type="text" id="Location" name="Location" maxlength="40">
            </div>

            <button type="submit" id="submitBtn" class="btn-primary">Send Wish Note</button>
        </form>
    </div>
</div>

<!-- Verification modal -->
<div class="modal-overlay" id="verifyModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal">
        <h3 id="modalTitle">Verify Target Data</h3>

        <div class="summary">
            <div class="summary-row"><span class="summary-label">First Name</span><span id="vFirstName"></span></div>
            <div class="summary-row"><span class="summary-label">Last Name</span><span id="vLastName"></span></div>
            <div class="summary-row"><span class="summary-label">Government Position</span><span id="vGovPosition"></span></div>
            <div class="summary-row"><span class="summary-label">Location</span><span id="vLocation"></span></div>
        </div>

        <div class="modal-actions">
            <button type="button" id="cancelBtn" class="btn-secondary">Cancel</button>
            <button type="button" id="confirmBtn" class="btn-primary">Confirm &amp; Send</button>
        </div>
    </div>
</div>

<script>
    (function () {
        var form       = document.getElementById("wishForm");
        var modal      = document.getElementById("verifyModal");
        var cancelBtn  = document.getElementById("cancelBtn");
        var confirmBtn = document.getElementById("confirmBtn");

        function closeModal() {
            modal.classList.remove("open");
        }

        // Intercept the submission and show the verification modal instead.
        // (Native "required" validation runs first, so this only fires when valid.)
        form.addEventListener("submit", function (e) {
            e.preventDefault();

            // textContent is used so user input is never interpreted as HTML.
            document.getElementById("vFirstName").textContent   = form.FirstName.value.trim();
            document.getElementById("vLastName").textContent    = form.LastName.value.trim();
            document.getElementById("vGovPosition").textContent = form.GovPosition.value.trim();
            document.getElementById("vLocation").textContent    = form.Location.value.trim() || "—";

            modal.classList.add("open");
            confirmBtn.focus();
        });

        cancelBtn.addEventListener("click", closeModal);

        // Clicking the dark backdrop or pressing Escape also closes the modal.
        modal.addEventListener("click", function (e) {
            if (e.target === modal) closeModal();
        });
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") closeModal();
        });

        // form.submit() does not trigger the "submit" event, so it bypasses the interception.
        confirmBtn.addEventListener("click", function () {
            form.submit();
        });
    })();
</script>

</body>
</html>
