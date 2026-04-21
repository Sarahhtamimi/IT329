<?php include 'auth_user.php'; ?>
<?php

include 'db_connection.php';
 
$errors   = [];
$success  = false;
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    // ── 1. Sanitise basic fields ──────────────────────────────────────────────
    $name        = trim($_POST['name']        ?? '');
    $categoryID  = (int)($_POST['categoryID'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $userID = $_SESSION['userID'];         // set by auth_user.php
 
    if (!$name)        $errors[] = 'Recipe name is required.';
    if (!$categoryID)  $errors[] = 'Please choose a category.';
    if (!$description) $errors[] = 'Description is required.';
 
    // ── 2. Upload photo (required) ────────────────────────────────────────────
    $photoFileName = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $ext_check = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext_check, ['jpg','jpeg','png','gif','webp'])) {
            $errors[] = 'Photo must be a JPG, PNG, GIF, or WEBP image.';
        } else {
            $ext           = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photoFileName = uniqid('recipe_', true) . '.' . $ext;
            $uploadDir = 'images/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photoFileName)) {
                $errors[] = 'Failed to save the photo. Please try again.';
                $photoFileName = '';
            }
        }
    } else {
        $errors[] = 'A recipe photo is required.';
    }
 
    // ── 3. Upload video (optional) ────────────────────────────────────────────
    $videoFilePath = null;
    if (isset($_FILES['videoFile']) && $_FILES['videoFile']['error'] === UPLOAD_ERR_OK) {
        $allowedVid = ['video/mp4','video/quicktime','video/x-msvideo','video/webm'];
        $ext_vid_check = strtolower(pathinfo($_FILES['videoFile']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext_vid_check, ['mp4','mov','avi','webm'])) {
            $errors[] = 'Video must be MP4, MOV, AVI, or WEBM.';
        } else {
            $extV          = pathinfo($_FILES['videoFile']['name'], PATHINFO_EXTENSION);
            $videoFilePath = uniqid('video_', true) . '.' . $extV;
            $uploadDirV = 'videos/';
            if (!is_dir($uploadDirV)) mkdir($uploadDirV, 0755, true);
            if (!move_uploaded_file($_FILES['videoFile']['tmp_name'], $uploadDirV . $videoFilePath)) {
                $errors[] = 'Failed to save the video. Please try again.';
                $videoFilePath = null;
            }
        }
    } elseif (!empty(trim($_POST['videoLink'] ?? ''))) {
        // Store the URL as the video path
        $videoFilePath = trim($_POST['videoLink']);
    }
 
    // ── 4. Validate ingredients & instructions arrays ─────────────────────────
    $ingredientNames = $_POST['ingredientName']     ?? [];
    $ingredientQtys  = $_POST['ingredientQuantity'] ?? [];
    $steps           = $_POST['step']               ?? [];
 
    if (empty($ingredientNames) || count(array_filter(array_map('trim', $ingredientNames))) === 0)
        $errors[] = 'Add at least one ingredient.';
    if (empty($steps) || count(array_filter(array_map('trim', $steps))) === 0)
        $errors[] = 'Add at least one instruction step.';
 
    // ── 5. Insert into DB (only when no errors) ───────────────────────────────
    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Recipe row
            $stmt = $conn->prepare(
                "INSERT INTO Recipe (userID, categoryID, NAME, description, photoFileName, videoFilePath)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('iissss', $userID, $categoryID, $name, $description, $photoFileName, $videoFilePath);
            $stmt->execute();
            $recipeID = $conn->insert_id;
            $stmt->close();
 
            // Ingredients
            $stmtIng = $conn->prepare(
                "INSERT INTO Ingredients (recipeID, ingredientName, ingredientQuantity) VALUES (?, ?, ?)"
            );
            foreach ($ingredientNames as $i => $ingName) {
                $ingName = trim($ingName);
                $ingQty  = trim($ingredientQtys[$i] ?? '');
                if ($ingName === '') continue;
                $stmtIng->bind_param('iss', $recipeID, $ingName, $ingQty);
                $stmtIng->execute();
            }
            $stmtIng->close();
 
            // Instructions
            $stmtIns = $conn->prepare(
                "INSERT INTO Instructions (recipeID, step, stepOrder) VALUES (?, ?, ?)"
            );
            $order = 1;
            foreach ($steps as $step) {
                $step = trim($step);
                if ($step === '') continue;
                $stmtIns->bind_param('isi', $recipeID, $step, $order);
                $stmtIns->execute();
                $order++;
            }
            $stmtIns->close();
 
            $conn->commit();
            // Redirect to My Recipes after success
            header('Location: myRecipes.php?added=1');
            exit;
 
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
 
// ── Load categories for the dropdown ─────────────────────────────────────────
$categories = [];
$catResult  = $conn->query("SELECT id, categoryName FROM RecipeCategory ORDER BY id");
while ($row = $catResult->fetch_assoc()) $categories[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
 
<head>
  <meta charset="UTF-8">
  <title>Add Recipe – Palm Glow</title>
 
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600&display=swap"
    rel="stylesheet">
 
  <style>
    :root {
      --matcha: #809671;
      --almond: #E5E0D8;
      --pistachio: #B3B792;
      --chai: #D2AB80;
      --carob: #725C3A;
      --vanilla: #E5D2B8;
      --white: #FFFFFF;
      --ink: #2C2C2C;
      --border: rgba(114, 92, 58, .16);
      --r-lg: 20px;
      --r-md: 16px;
      --r-pill: 999px;
      --shadow: 0 16px 40px rgba(114, 92, 58, .12);
      --shadow-soft: 0 10px 24px rgba(114, 92, 58, .10);
      --container: 1100px;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Inter, system-ui, Arial, sans-serif;
      color: var(--ink);
      background: linear-gradient(180deg, var(--almond), rgba(229, 224, 216, .85));
    }
    .pattern {
      position: fixed; inset: 0; pointer-events: none; opacity: .09;
      background-image:
        linear-gradient(45deg, rgba(179,183,146,.35) 25%, transparent 25%),
        linear-gradient(-45deg, rgba(179,183,146,.35) 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, rgba(179,183,146,.35) 75%),
        linear-gradient(-45deg, transparent 75%, rgba(179,183,146,.35) 75%);
      background-size: 34px 34px; z-index: -2;
    }
    a { color: inherit; text-decoration: none; }
    .container { width: min(var(--container), calc(100% - 32px)); margin: 0 auto; }
 
    /* Header */
    header {
      position: sticky; top: 0; z-index: 10;
      background: rgba(229,224,216,.78); backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(114,92,58,.10);
    }
    .nav-wrap {
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      align-items: center;
      gap: 16px; padding: 14px 0;
    }
    .brand { display: flex; align-items: center; gap: 12px; min-width: 220px; justify-self: start; }
    .brand img {
      width: 44px; height: 44px; object-fit: contain; border-radius: 12px;
      background: rgba(255,255,255,.35); border: 1px solid rgba(114,92,58,.12);
    }
    .brand .name {
      font-family: "Cormorant Garamond", serif; font-weight: 700;
      font-size: 22px; color: var(--carob); line-height: 1;
    }
    .brand .tag { font-size: 12px; opacity: .75; margin-top: 3px; }
    nav { justify-self: center; display: flex; gap: 10px; flex-wrap: nowrap; justify-content: center; white-space: nowrap; }
    .nav-link {
      padding: 10px 14px; border-radius: 14px; border: 1px solid transparent;
      font-size: 13.5px; opacity: .86; transition: .2s;
    }
    .nav-link:hover {
      background: rgba(255,255,255,.55); border-color: rgba(114,92,58,.12); opacity: 1;
    }
 
    /* Main */
    main { padding: 40px 16px 60px; }
    h1 {
      font-family: 'Cormorant Garamond', serif; font-size: 42px; font-weight: 700;
      color: var(--carob); text-align: center; margin-bottom: 28px;
    }
    form {
      background: rgba(255,255,255,.72); backdrop-filter: blur(6px);
      border: 1px solid var(--border); border-radius: var(--r-lg);
      box-shadow: var(--shadow-soft); max-width: 700px; margin: auto; padding: 32px;
    }
    label {
      display: block; margin-top: 18px; margin-bottom: 6px;
      font-size: 13px; font-weight: 500; opacity: .85;
    }
    input, textarea, select {
      width: 100%; padding: 11px 14px; font-size: 14px;
      border-radius: var(--r-pill); border: 1px solid var(--border);
      font-family: 'Inter', sans-serif; background: rgba(255,255,255,.85);
    }
    textarea { border-radius: 16px; resize: none; line-height: 1.6; }
    input:focus, textarea:focus, select:focus {
      outline: none; border-color: rgba(128,150,113,.5);
    }
    .row { display: flex; gap: 10px; margin-bottom: 10px; }
    .row button {
      border: none; background: rgba(210,171,128,.15); color: var(--carob);
      width: 36px; border-radius: 12px; cursor: pointer;
    }
    .add-btn {
      margin-top: 6px; margin-bottom: 10px; padding: 8px 14px;
      border-radius: var(--r-pill); border: 1px dashed rgba(128,150,113,.45);
      background: rgba(128,150,113,.08); color: var(--carob); font-size: 13px; cursor: pointer;
    }
    .submit-btn {
      margin-top: 28px; width: 100%; padding: 14px; border-radius: var(--r-pill);
      background: var(--matcha); color: white; border: none; font-size: 15px;
      font-weight: 500; cursor: pointer; box-shadow: 0 6px 18px rgba(128,150,113,.25);
    }
    .alert-error {
      background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c;
      border-radius: 12px; padding: 12px 16px; margin-bottom: 18px; font-size: 13.5px;
    }
    .alert-error ul { margin: 6px 0 0; padding-left: 18px; }
 
    /* Footer */
    .footer {
      margin-top: 28px; padding: 36px 0 18px;
      background: linear-gradient(180deg, rgba(210,171,128,.35), rgba(229,210,184,.55));
      border-top: 1px solid rgba(114,92,58,.12);
    }
    .footer-grid {
      display: grid; grid-template-columns: 1.4fr 1fr 1.1fr; gap: 18px; align-items: start;
    }
    .footer-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
    .footer-brand img {
      width: 44px; height: 44px; object-fit: contain; border-radius: 14px;
      border: 1px solid rgba(114,92,58,.12); background: rgba(255,255,255,.35);
    }
    .footer-brand b { color: var(--carob); font-size: 18px; }
    .footer-mini { font-size: 12px; opacity: .75; margin-top: 2px; }
    .footer-col h4 {
      margin: 6px 0 10px; font-family: "Cormorant Garamond", serif;
      font-size: 20px; color: var(--carob);
    }
    .footer-text { margin: 0; font-size: 13.5px; line-height: 1.7; opacity: .82; max-width: 52ch; }
    .footer-text.small { font-size: 12.8px; opacity: .78; margin-bottom: 10px; }
    .footer-links { display: flex; flex-direction: column; gap: 8px; font-size: 13px; opacity: .82; }
    .subscribe { display: flex; gap: 10px; flex-wrap: wrap; }
    .subscribe input {
      flex: 1 1 180px; padding: 12px 14px; border-radius: var(--r-pill);
      border: 1px solid rgba(114,92,58,.14); background: rgba(255,255,255,.70); outline: none;
    }
    .subscribe input:focus { border-color: rgba(128,150,113,.55); }
    .subscribe button {
      padding: 12px 16px; border-radius: var(--r-pill);
      border: 1px solid rgba(128,150,113,.55); background: var(--matcha);
      color: #fff; cursor: pointer; font-weight: 600;
    }
    .subscribe button:hover { opacity: .93; }
    .sub-note { margin-top: 10px; font-size: 12.5px; color: var(--carob); opacity: .9; min-height: 16px; }
    .footer-bottom {
      margin-top: 18px; padding-top: 14px; border-top: 1px solid rgba(114,92,58,.10);
      display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap;
      font-size: 12.5px; opacity: .84;
    }
    @media (max-width: 980px) {
      .footer-grid { grid-template-columns: 1fr; }
      .footer-text { max-width: 100%; }
      nav { display: none; }
    }
  </style>
</head>
 
<body>
  <div class="pattern" aria-hidden="true"></div>
 
  <header>
    <div class="container nav-wrap">
      <a class="brand" href="#">
        <img src="images/palmLogo.png" alt="Palm Glow Logo">
        <div>
          <div class="name">Palm Glow</div>
          <div class="tag">healthy global recipes • Saudi touch</div>
        </div>
      </a>
      <nav>
        <a class="nav-link" href="user.php">User Page</a>
        <a class="nav-link" href="myRecipes.php">My Recipes</a>
        <a class="nav-link" href="addRecipe.php">Add Recipe</a>
      </nav>
    </div>
  </header>
 
  <main>
    <h1>Add Recipe</h1>
 
    <form method="POST" enctype="multipart/form-data" id="recipeForm">
 
      <?php if (!empty($errors)): ?>
        <div class="alert-error">
          <strong>Please fix the following:</strong>
          <ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
        </div>
      <?php endif; ?>
 
      <label>Recipe Name</label>
      <input type="text" name="name" placeholder="Chocolate cake"
             value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
 
      <label>Category</label>
      <select name="categoryID" required>
        <option value="">– Select category –</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>"
            <?= (($_POST['categoryID'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['categoryName']) ?>
          </option>
        <?php endforeach; ?>
      </select>
 
      <label>Description</label>
      <textarea name="description" rows="3" placeholder="Describe your recipe…" required
      ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
 
      <label>Photo <span style="opacity:.6">(required)</span></label>
      <input type="file" name="photo" id="photo" accept="image/*" required>
 
      <label>Ingredients</label>
      <div id="ingredients">
        <div class="row">
          <input type="text" name="ingredientName[]" placeholder="Flour" required>
          <input type="text" name="ingredientQuantity[]" placeholder="1 Cup" required>
          <button type="button" onclick="removeRow(this)">✕</button>
        </div>
      </div>
      <button type="button" class="add-btn" onclick="addIngredient()">+ Add Ingredient</button>
 
      <label>Instructions</label>
      <div id="instructions">
        <div class="row">
          <input type="text" name="step[]" placeholder="Preheat the oven to 180°C…" required>
          <button type="button" onclick="removeRow(this)">✕</button>
        </div>
      </div>
      <button type="button" class="add-btn" onclick="addInstruction()">+ Add Step</button>
 
      <label>Recipe Video <span style="opacity:.6">(optional – upload file)</span></label>
      <input type="file" name="videoFile" id="videoFile" accept="video/*">
 
      <label>Or Video Link</label>
      <input type="url" name="videoLink" id="videoLink" placeholder="https://youtube.com/..."
             value="<?= htmlspecialchars($_POST['videoLink'] ?? '') ?>">
 
      <button type="submit" class="submit-btn">Add Recipe</button>
    </form>
  </main>
 
  <footer class="footer">
    <div class="container footer-grid">
      <div class="footer-about">
        <div class="footer-brand">
          <img src="images/palmLogo.png" alt="Palm Glow Logo">
          <div><b>Palm Glow</b><div class="footer-mini">clean wellness • warm Saudi touch</div></div>
        </div>
        <p class="footer-text">Save your favorites, explore quick ideas, and add your own creations — all in one warm vibe.</p>
      </div>
      <div class="footer-col">
        <h4>Contact</h4>
        <div class="footer-links">
          <span>Email: help@palmglow.com</span>
          <span>Location: Riyadh, Saudi Arabia</span>
        </div>
      </div>
      <div class="footer-col">
        <h4>Subscribe</h4>
        <p class="footer-text small">Get new recipes &amp; wellness picks</p>
        <form class="subscribe" id="subscribeForm">
          <input type="email" id="subEmail" placeholder="Enter your email" required>
          <button type="submit">Subscribe</button>
        </form>
        <div class="sub-note" id="subNote" aria-live="polite"></div>
      </div>
    </div>
    <div class="container footer-bottom"><span>© 2026 Palm Glow</span></div>
  </footer>
 
  <script>
    function addIngredient() {
      document.getElementById('ingredients').insertAdjacentHTML('beforeend', `
        <div class="row">
          <input type="text" name="ingredientName[]" placeholder="Ingredient" required>
          <input type="text" name="ingredientQuantity[]" placeholder="Quantity" required>
          <button type="button" onclick="removeRow(this)">✕</button>
        </div>`);
    }
    function addInstruction() {
      document.getElementById('instructions').insertAdjacentHTML('beforeend', `
        <div class="row">
          <input type="text" name="step[]" placeholder="Step" required>
          <button type="button" onclick="removeRow(this)">✕</button>
        </div>`);
    }
    function removeRow(btn) { btn.parentElement.remove(); }
 
    document.getElementById('photo').addEventListener('change', function () {
      if (this.files[0] && !this.files[0].type.startsWith('image/')) {
        alert('Please upload an image file only (jpg, png, etc.)');
        this.value = '';
      }
    });
    document.getElementById('videoFile').addEventListener('change', function () {
      if (this.files[0] && !this.files[0].type.startsWith('video/')) {
        alert('Please upload a video file only (mp4, mov, etc.)');
        this.value = '';
      }
    });
  </script>
</body>
</html>
<?php

include 'db_connection.php';
 
$errors   = [];
$success  = false;
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    // ── 1. Sanitise basic fields ──────────────────────────────────────────────
    $name        = trim($_POST['name']        ?? '');
    $categoryID  = (int)($_POST['categoryID'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $userID = $_SESSION['userID'];         // set by auth_user.php
 
    if (!$name)        $errors[] = 'Recipe name is required.';
    if (!$categoryID)  $errors[] = 'Please choose a category.';
    if (!$description) $errors[] = 'Description is required.';
 
    // ── 2. Upload photo (required) ────────────────────────────────────────────
    $photoFileName = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $ext_check = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext_check, ['jpg','jpeg','png','gif','webp'])) {
            $errors[] = 'Photo must be a JPG, PNG, GIF, or WEBP image.';
        } else {
            $ext           = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photoFileName = uniqid('recipe_', true) . '.' . $ext;
            $uploadDir = 'images/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photoFileName)) {
                $errors[] = 'Failed to save the photo. Please try again.';
                $photoFileName = '';
            }
        }
    } else {
        $errors[] = 'A recipe photo is required.';
    }
 
    // ── 3. Upload video (optional) ────────────────────────────────────────────
    $videoFilePath = null;
    if (isset($_FILES['videoFile']) && $_FILES['videoFile']['error'] === UPLOAD_ERR_OK) {
        $allowedVid = ['video/mp4','video/quicktime','video/x-msvideo','video/webm'];
        $ext_vid_check = strtolower(pathinfo($_FILES['videoFile']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext_vid_check, ['mp4','mov','avi','webm'])) {
            $errors[] = 'Video must be MP4, MOV, AVI, or WEBM.';
        } else {
            $extV          = pathinfo($_FILES['videoFile']['name'], PATHINFO_EXTENSION);
            $videoFilePath = uniqid('video_', true) . '.' . $extV;
            $uploadDirV = 'videos/';
            if (!is_dir($uploadDirV)) mkdir($uploadDirV, 0755, true);
            if (!move_uploaded_file($_FILES['videoFile']['tmp_name'], $uploadDirV . $videoFilePath)) {
                $errors[] = 'Failed to save the video. Please try again.';
                $videoFilePath = null;
            }
        }
    } elseif (!empty(trim($_POST['videoLink'] ?? ''))) {
        // Store the URL as the video path
        $videoFilePath = trim($_POST['videoLink']);
    }
 
    // ── 4. Validate ingredients & instructions arrays ─────────────────────────
    $ingredientNames = $_POST['ingredientName']     ?? [];
    $ingredientQtys  = $_POST['ingredientQuantity'] ?? [];
    $steps           = $_POST['step']               ?? [];
 
    if (empty($ingredientNames) || count(array_filter(array_map('trim', $ingredientNames))) === 0)
        $errors[] = 'Add at least one ingredient.';
    if (empty($steps) || count(array_filter(array_map('trim', $steps))) === 0)
        $errors[] = 'Add at least one instruction step.';
 
    // ── 5. Insert into DB (only when no errors) ───────────────────────────────
    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Recipe row
            $stmt = $conn->prepare(
                "INSERT INTO Recipe (userID, categoryID, NAME, description, photoFileName, videoFilePath)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('iissss', $userID, $categoryID, $name, $description, $photoFileName, $videoFilePath);
            $stmt->execute();
            $recipeID = $conn->insert_id;
            $stmt->close();
 
            // Ingredients
            $stmtIng = $conn->prepare(
                "INSERT INTO Ingredients (recipeID, ingredientName, ingredientQuantity) VALUES (?, ?, ?)"
            );
            foreach ($ingredientNames as $i => $ingName) {
                $ingName = trim($ingName);
                $ingQty  = trim($ingredientQtys[$i] ?? '');
                if ($ingName === '') continue;
                $stmtIng->bind_param('iss', $recipeID, $ingName, $ingQty);
                $stmtIng->execute();
            }
            $stmtIng->close();
 
            // Instructions
            $stmtIns = $conn->prepare(
                "INSERT INTO Instructions (recipeID, step, stepOrder) VALUES (?, ?, ?)"
            );
            $order = 1;
            foreach ($steps as $step) {
                $step = trim($step);
                if ($step === '') continue;
                $stmtIns->bind_param('isi', $recipeID, $step, $order);
                $stmtIns->execute();
                $order++;
            }
            $stmtIns->close();
 
            $conn->commit();
            // Redirect to My Recipes after success
            header('Location: myRecipes.php?added=1');
            exit;
 
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
 
// ── Load categories for the dropdown ─────────────────────────────────────────
$categories = [];
$catResult  = $conn->query("SELECT id, categoryName FROM RecipeCategory ORDER BY id");
while ($row = $catResult->fetch_assoc()) $categories[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
 
<head>
  <meta charset="UTF-8">
  <title>Add Recipe – Palm Glow</title>
 
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600&display=swap"
    rel="stylesheet">
 
  <style>
    :root {
      --matcha: #809671;
      --almond: #E5E0D8;
      --pistachio: #B3B792;
      --chai: #D2AB80;
      --carob: #725C3A;
      --vanilla: #E5D2B8;
      --white: #FFFFFF;
      --ink: #2C2C2C;
      --border: rgba(114, 92, 58, .16);
      --r-lg: 20px;
      --r-md: 16px;
      --r-pill: 999px;
      --shadow: 0 16px 40px rgba(114, 92, 58, .12);
      --shadow-soft: 0 10px 24px rgba(114, 92, 58, .10);
      --container: 1100px;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Inter, system-ui, Arial, sans-serif;
      color: var(--ink);
      background: linear-gradient(180deg, var(--almond), rgba(229, 224, 216, .85));
    }
    .pattern {
      position: fixed; inset: 0; pointer-events: none; opacity: .09;
      background-image:
        linear-gradient(45deg, rgba(179,183,146,.35) 25%, transparent 25%),
        linear-gradient(-45deg, rgba(179,183,146,.35) 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, rgba(179,183,146,.35) 75%),
        linear-gradient(-45deg, transparent 75%, rgba(179,183,146,.35) 75%);
      background-size: 34px 34px; z-index: -2;
    }
    a { color: inherit; text-decoration: none; }
    .container { width: min(var(--container), calc(100% - 32px)); margin: 0 auto; }
 
    /* Header */
    header {
      position: sticky; top: 0; z-index: 10;
      background: rgba(229,224,216,.78); backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(114,92,58,.10);
    }
    .nav-wrap {
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      align-items: center;
      gap: 16px; padding: 14px 0;
    }
    .brand { display: flex; align-items: center; gap: 12px; min-width: 220px; justify-self: start; }
    .brand img {
      width: 44px; height: 44px; object-fit: contain; border-radius: 12px;
      background: rgba(255,255,255,.35); border: 1px solid rgba(114,92,58,.12);
    }
    .brand .name {
      font-family: "Cormorant Garamond", serif; font-weight: 700;
      font-size: 22px; color: var(--carob); line-height: 1;
    }
    .brand .tag { font-size: 12px; opacity: .75; margin-top: 3px; }
    nav { justify-self: center; display: flex; gap: 10px; flex-wrap: nowrap; justify-content: center; white-space: nowrap; }
    .nav-link {
      padding: 10px 14px; border-radius: 14px; border: 1px solid transparent;
      font-size: 13.5px; opacity: .86; transition: .2s;
    }
    .nav-link:hover {
      background: rgba(255,255,255,.55); border-color: rgba(114,92,58,.12); opacity: 1;
    }
 
    /* Main */
    main { padding: 40px 16px 60px; }
    h1 {
      font-family: 'Cormorant Garamond', serif; font-size: 42px; font-weight: 700;
      color: var(--carob); text-align: center; margin-bottom: 28px;
    }
    form {
      background: rgba(255,255,255,.72); backdrop-filter: blur(6px);
      border: 1px solid var(--border); border-radius: var(--r-lg);
      box-shadow: var(--shadow-soft); max-width: 700px; margin: auto; padding: 32px;
    }
    label {
      display: block; margin-top: 18px; margin-bottom: 6px;
      font-size: 13px; font-weight: 500; opacity: .85;
    }
    input, textarea, select {
      width: 100%; padding: 11px 14px; font-size: 14px;
      border-radius: var(--r-pill); border: 1px solid var(--border);
      font-family: 'Inter', sans-serif; background: rgba(255,255,255,.85);
    }
    textarea { border-radius: 16px; resize: none; line-height: 1.6; }
    input:focus, textarea:focus, select:focus {
      outline: none; border-color: rgba(128,150,113,.5);
    }
    .row { display: flex; gap: 10px; margin-bottom: 10px; }
    .row button {
      border: none; background: rgba(210,171,128,.15); color: var(--carob);
      width: 36px; border-radius: 12px; cursor: pointer;
    }
    .add-btn {
      margin-top: 6px; margin-bottom: 10px; padding: 8px 14px;
      border-radius: var(--r-pill); border: 1px dashed rgba(128,150,113,.45);
      background: rgba(128,150,113,.08); color: var(--carob); font-size: 13px; cursor: pointer;
    }
    .submit-btn {
      margin-top: 28px; width: 100%; padding: 14px; border-radius: var(--r-pill);
      background: var(--matcha); color: white; border: none; font-size: 15px;
      font-weight: 500; cursor: pointer; box-shadow: 0 6px 18px rgba(128,150,113,.25);
    }
    .alert-error {
      background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c;
      border-radius: 12px; padding: 12px 16px; margin-bottom: 18px; font-size: 13.5px;
    }
    .alert-error ul { margin: 6px 0 0; padding-left: 18px; }
 
    /* Footer */
    .footer {
      margin-top: 28px; padding: 36px 0 18px;
      background: linear-gradient(180deg, rgba(210,171,128,.35), rgba(229,210,184,.55));
      border-top: 1px solid rgba(114,92,58,.12);
    }
    .footer-grid {
      display: grid; grid-template-columns: 1.4fr 1fr 1.1fr; gap: 18px; align-items: start;
    }
    .footer-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
    .footer-brand img {
      width: 44px; height: 44px; object-fit: contain; border-radius: 14px;
      border: 1px solid rgba(114,92,58,.12); background: rgba(255,255,255,.35);
    }
    .footer-brand b { color: var(--carob); font-size: 18px; }
    .footer-mini { font-size: 12px; opacity: .75; margin-top: 2px; }
    .footer-col h4 {
      margin: 6px 0 10px; font-family: "Cormorant Garamond", serif;
      font-size: 20px; color: var(--carob);
    }
    .footer-text { margin: 0; font-size: 13.5px; line-height: 1.7; opacity: .82; max-width: 52ch; }
    .footer-text.small { font-size: 12.8px; opacity: .78; margin-bottom: 10px; }
    .footer-links { display: flex; flex-direction: column; gap: 8px; font-size: 13px; opacity: .82; }
    .subscribe { display: flex; gap: 10px; flex-wrap: wrap; }
    .subscribe input {
      flex: 1 1 180px; padding: 12px 14px; border-radius: var(--r-pill);
      border: 1px solid rgba(114,92,58,.14); background: rgba(255,255,255,.70); outline: none;
    }
    .subscribe input:focus { border-color: rgba(128,150,113,.55); }
    .subscribe button {
      padding: 12px 16px; border-radius: var(--r-pill);
      border: 1px solid rgba(128,150,113,.55); background: var(--matcha);
      color: #fff; cursor: pointer; font-weight: 600;
    }
    .subscribe button:hover { opacity: .93; }
    .sub-note { margin-top: 10px; font-size: 12.5px; color: var(--carob); opacity: .9; min-height: 16px; }
    .footer-bottom {
      margin-top: 18px; padding-top: 14px; border-top: 1px solid rgba(114,92,58,.10);
      display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap;
      font-size: 12.5px; opacity: .84;
    }
    @media (max-width: 980px) {
      .footer-grid { grid-template-columns: 1fr; }
      .footer-text { max-width: 100%; }
      nav { display: none; }
    }
  </style>
</head>
 
<body>
  <div class="pattern" aria-hidden="true"></div>
 
  <header>
    <div class="container nav-wrap">
      <a class="brand" href="#">
        <img src="images/palmLogo.png" alt="Palm Glow Logo">
        <div>
          <div class="name">Palm Glow</div>
          <div class="tag">healthy global recipes • Saudi touch</div>
        </div>
      </a>
      <nav>
        <a class="nav-link" href="user.php">User Page</a>
        <a class="nav-link" href="myRecipes.php">My Recipes</a>
        <a class="nav-link" href="addRecipe.php">Add Recipe</a>
      </nav>
    </div>
  </header>
 
  <main>
    <h1>Add Recipe</h1>
 
    <form method="POST" enctype="multipart/form-data" id="recipeForm">
 
      <?php if (!empty($errors)): ?>
        <div class="alert-error">
          <strong>Please fix the following:</strong>
          <ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
        </div>
      <?php endif; ?>
 
      <label>Recipe Name</label>
      <input type="text" name="name" placeholder="Chocolate cake"
             value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
 
      <label>Category</label>
      <select name="categoryID" required>
        <option value="">– Select category –</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>"
            <?= (($_POST['categoryID'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['categoryName']) ?>
          </option>
        <?php endforeach; ?>
      </select>
 
      <label>Description</label>
      <textarea name="description" rows="3" placeholder="Describe your recipe…" required
      ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
 
      <label>Photo <span style="opacity:.6">(required)</span></label>
      <input type="file" name="photo" id="photo" accept="image/*" required>
 
      <label>Ingredients</label>
      <div id="ingredients">
        <div class="row">
          <input type="text" name="ingredientName[]" placeholder="Flour" required>
          <input type="text" name="ingredientQuantity[]" placeholder="1 Cup" required>
          <button type="button" onclick="removeRow(this)">✕</button>
        </div>
      </div>
      <button type="button" class="add-btn" onclick="addIngredient()">+ Add Ingredient</button>
 
      <label>Instructions</label>
      <div id="instructions">
        <div class="row">
          <input type="text" name="step[]" placeholder="Preheat the oven to 180°C…" required>
          <button type="button" onclick="removeRow(this)">✕</button>
        </div>
      </div>
      <button type="button" class="add-btn" onclick="addInstruction()">+ Add Step</button>
 
      <label>Recipe Video <span style="opacity:.6">(optional – upload file)</span></label>
      <input type="file" name="videoFile" id="videoFile" accept="video/*">
 
      <label>Or Video Link</label>
      <input type="url" name="videoLink" id="videoLink" placeholder="https://youtube.com/..."
             value="<?= htmlspecialchars($_POST['videoLink'] ?? '') ?>">
 
      <button type="submit" class="submit-btn">Add Recipe</button>
    </form>
  </main>
 
  <footer class="footer">
    <div class="container footer-grid">
      <div class="footer-about">
        <div class="footer-brand">
          <img src="images/palmLogo.png" alt="Palm Glow Logo">
          <div><b>Palm Glow</b><div class="footer-mini">clean wellness • warm Saudi touch</div></div>
        </div>
        <p class="footer-text">Save your favorites, explore quick ideas, and add your own creations — all in one warm vibe.</p>
      </div>
      <div class="footer-col">
        <h4>Contact</h4>
        <div class="footer-links">
          <span>Email: help@palmglow.com</span>
          <span>Location: Riyadh, Saudi Arabia</span>
        </div>
      </div>
      <div class="footer-col">
        <h4>Subscribe</h4>
        <p class="footer-text small">Get new recipes &amp; wellness picks</p>
        <form class="subscribe" id="subscribeForm">
          <input type="email" id="subEmail" placeholder="Enter your email" required>
          <button type="submit">Subscribe</button>
        </form>
        <div class="sub-note" id="subNote" aria-live="polite"></div>
      </div>
    </div>
    <div class="container footer-bottom"><span>© 2026 Palm Glow</span></div>
  </footer>
 
  <script>
    function addIngredient() {
      document.getElementById('ingredients').insertAdjacentHTML('beforeend', `
        <div class="row">
          <input type="text" name="ingredientName[]" placeholder="Ingredient" required>
          <input type="text" name="ingredientQuantity[]" placeholder="Quantity" required>
          <button type="button" onclick="removeRow(this)">✕</button>
        </div>`);
    }
    function addInstruction() {
      document.getElementById('instructions').insertAdjacentHTML('beforeend', `
        <div class="row">
          <input type="text" name="step[]" placeholder="Step" required>
          <button type="button" onclick="removeRow(this)">✕</button>
        </div>`);
    }
    function removeRow(btn) { btn.parentElement.remove(); }
 
    document.getElementById('photo').addEventListener('change', function () {
      if (this.files[0] && !this.files[0].type.startsWith('image/')) {
        alert('Please upload an image file only (jpg, png, etc.)');
        this.value = '';
      }
    });
    document.getElementById('videoFile').addEventListener('change', function () {
      if (this.files[0] && !this.files[0].type.startsWith('video/')) {
        alert('Please upload a video file only (mp4, mov, etc.)');
        this.value = '';
      }
    });
  </script>
</body>
</html>
