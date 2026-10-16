<?php
// ---------------------------------------------------------------
// Database connection (local MariaDB / MySQL)
// ---------------------------------------------------------------
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "birthday_wishnote";

mysqli_report(MYSQLI_REPORT_OFF); // handle errors manually below

$conn = @new mysqli($servername, $username, $password, $dbname);

$successMessage = "";
$errorMessage   = "";

if ($conn->connect_error) {
    $errorMessage = "Database connection failed. Please check that MySQL is running.";
}

// ---------------------------------------------------------------
// Handle form submission
// ---------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && !$errorMessage) {
    // Capture all four variables from the form
    $FirstName   = trim($_POST["FirstName"]   ?? "");
    $LastName    = trim($_POST["LastName"]    ?? "");
    $GovPosition = trim($_POST["GovPosition"] ?? "");
    $Location    = trim($_POST["Location"]    ?? "");

    // ===========================================================
    // INTENTIONAL FLAW (for QA testing purposes):
    // The Location value is captured above, but it is deliberately
    // OMITTED from both the INSERT statement and bind_param().
    // As a result, only FirstName, LastName and GovPosition are
    // saved to the database; the Location data is silently lost
    // even though the user sees a success message.
    // A tester should catch this by comparing the entered data
    // with the contents of the table in MySQL.
    // ===========================================================
    $sql  = "INSERT INTO main_corrupt_politicians (FirstName, LastName, GovPosition) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        // Fallback error if the query fails to prepare
        $errorMessage = "Error: the query could not be prepared. Please contact the administrator.";
    } else {
        // Note: $Location is NOT bound (the intentional flaw)
        $stmt->bind_param("sss", $FirstName, $LastName, $GovPosition);
        if ($stmt->execute()) {
            $successMessage = "Success! The wish note has been sent.";
        } else {
            $errorMessage = "Error: the wish note could not be saved.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kara David's Wish Note</title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0;
    padding: 20px;
    min-height: 100vh;
    background: #eceff1;
    font-family: "Segoe UI", Roboto, Arial, sans-serif;
    color: #263238;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .card {
    width: 100%;
    max-width: 460px;
    background: #ffffff;
    border-radius: 8px;
    padding: 28px 26px;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
  }
  h2 {
    margin: 0 0 20px;
    text-align: center;
    color: #1976d2;
  }
  label {
    display: block;
    margin: 14px 0 6px;
    font-weight: 600;
    font-size: 0.92rem;
  }
  input[type="text"] {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #b0bec5;
    border-radius: 4px;
    font-size: 1rem;
  }
  input[type="text"]:focus {
    outline: none;
    border-color: #1976d2;
    box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.2);
  }
  .req { color: #d32f2f; }
  button {
    border: none;
    border-radius: 4px;
    padding: 11px 18px;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.2s;
  }
  .btn-primary { background: #1976d2; color: #fff; width: 100%; margin-top: 22px; }
  .btn-primary:hover { background: #0d47a1; }
  .btn-cancel { background: #cfd8dc; color: #263238; }
  .btn-cancel:hover { background: #b0bec5; }
  .msg { padding: 12px 14px; border-radius: 4px; margin-bottom: 16px; font-size: 0.95rem; }
  .msg.success { background: #e3f2fd; color: #0d47a1; border-left: 4px solid #1976d2; }
  .msg.error   { background: #ffebee; color: #b71c1c; border-left: 4px solid #d32f2f; }

  /* Modal */
  .overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    align-items: center;
    justify-content: center;
    padding: 20px;
    z-index: 10;
  }
  .overlay.open { display: flex; }
  .modal {
    background: #fff;
    width: 100%;
    max-width: 420px;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
  }
  .modal h3 { margin: 0 0 14px; color: #1976d2; }
  .modal dl { margin: 0 0 20px; }
  .modal dt { font-weight: 600; font-size: 0.85rem; color: #546e7a; margin-top: 10px; }
  .modal dd { margin: 2px 0 0; word-break: break-word; }
  .modal-actions { display: flex; justify-content: flex-end; gap: 10px; }
  .modal-actions .btn-primary { width: auto; margin-top: 0; }
</style>
</head>
<body>
<div class="card">
  <h2>Kara David's Wish Note</h2>

  <?php if ($successMessage): ?>
    <div class="msg success"><?= htmlspecialchars($successMessage) ?></div>
  <?php endif; ?>
  <?php if ($errorMessage): ?>
    <div class="msg error"><?= htmlspecialchars($errorMessage) ?></div>
  <?php endif; ?>

  <form id="wishForm" method="POST" action="">
    <label for="FirstName">Target's First Name <span class="req">*</span></label>
    <input type="text" id="FirstName" name="FirstName" maxlength="30" required>

    <label for="LastName">Target's Last Name <span class="req">*</span></label>
    <input type="text" id="LastName" name="LastName" maxlength="30" required>

    <label for="GovPosition">Target's Government Position <span class="req">*</span></label>
    <input type="text" id="GovPosition" name="GovPosition" maxlength="20" required>

    <label for="Location">Target's Location</label>
    <input type="text" id="Location" name="Location" maxlength="40">

    <button type="submit" class="btn-primary">Send Wish Note</button>
  </form>
</div>

<!-- Verification modal -->
<div class="overlay" id="overlay">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <h3 id="modalTitle">Verify Target Data</h3>
    <dl>
      <dt>First Name</dt><dd id="mFirst"></dd>
      <dt>Last Name</dt><dd id="mLast"></dd>
      <dt>Government Position</dt><dd id="mPos"></dd>
      <dt>Location</dt><dd id="mLoc"></dd>
    </dl>
    <div class="modal-actions">
      <button type="button" class="btn-cancel" id="cancelBtn">Cancel</button>
      <button type="button" class="btn-primary" id="confirmBtn">Confirm &amp; Send</button>
    </div>
  </div>
</div>

<script>
  var form    = document.getElementById("wishForm");
  var overlay = document.getElementById("overlay");

  form.addEventListener("submit", function (e) {
    e.preventDefault(); // intercept the submission

    // Show browser validation messages for required fields
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    document.getElementById("mFirst").textContent = form.FirstName.value;
    document.getElementById("mLast").textContent  = form.LastName.value;
    document.getElementById("mPos").textContent   = form.GovPosition.value;
    document.getElementById("mLoc").textContent   = form.Location.value || "(not provided)";
    overlay.classList.add("open");
  });

  document.getElementById("cancelBtn").addEventListener("click", function () {
    overlay.classList.remove("open");
  });

  document.getElementById("confirmBtn").addEventListener("click", function () {
    form.submit(); // programmatic submit bypasses the interception
  });
</script>
</body>
</html>
