<?php include 'auth_user.php'; ?>
<?php

include 'db_connection.php';
 
// ── Get recipe ID from URL ────────────────────────────────────────────────────
$recipeID = (int)($_GET['id'] ?? 0);
if (!$recipeID) {
    header('Location: myRecipes.php');
    exit;
}
 
// ── Load recipe (ownership check) ────────────────────────────────────────────
$userID = $_SESSION['userID'];
$stmt   = $conn->prepare(
    "SELECT r.*, rc.categoryName
     FROM Recipe r
     JOIN RecipeCategory rc ON r.categoryID = rc.id
     WHERE r.id = ? AND r.userID = ?"
);
$stmt->bind_param('ii', $recipeID, $userID);
$stmt->execute();
$recipe = $stmt->get_result()->fetch_assoc();
$stmt->close();
 
if (!$recipe) {
    // Not found or not owned by this user
    header('Location: myRecipes.php?error=notfound');
    exit;
}
 
// ── Load existing ingredients ─────────────────────────────────────────────────
$ingRows = [];
$res = $conn->prepare("SELECT ingredientName, ingredientQuantity FROM Ingredients WHERE recipeID = ? ORDER BY id");
$res->bind_param('i', $recipeID);
$res->execute();
$ingResult = $res->get_result();
while ($row = $ingResult->fetch_assoc()) $ingRows[] = $row;
$res->close();
 
// ── Load existing instructions ────────────────────────────────────────────────
$insRows = [];
$res2 = $conn->prepare("SELECT step FROM Instructions WHERE recipeID = ? ORDER BY stepOrder");
$res2->bind_param('i', $recipeID);
$res2->execute();
$insResult = $res2->get_result();
while ($row = $insResult->fetch_assoc()) $insRows[] = $row['step'];
$res2->close();
 
// ── Load categories ───────────────────────────────────────────────────────────
$categories = [];
$catResult  = $conn->query("SELECT id, categoryName FROM RecipeCategory ORDER BY id");
while ($row = $catResult->fetch_assoc()) $categories[] = $row;
 
$errors = [];
 
// ── Handle POST (save changes) ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    $name        = trim($_POST['name']        ?? '');
    $categoryID  = (int)($_POST['categoryID'] ?? 0);
    $description = trim($_POST['description'] ?? '');
 
    if (!$name)        $errors[] = 'Recipe name is required.';
    if (!$categoryID)  $errors[] = 'Please choose a category.';
    if (!$description) $errors[] = 'Description is required.';
 
    // ── Photo: replace if new file uploaded, otherwise keep old ──────────────
    $photoFileName = $recipe['photoFileName']; // default: keep old
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $ext_check = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext_check, ['jpg','jpeg','png','gif','webp'])) {
            $errors[] = 'Photo must be JPG, PNG, GIF, or WEBP.';
        } else {
            $ext           = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $newPhoto      = uniqid('recipe_', true) . '.' . $ext;
            $uploadDir = 'images/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $newPhoto)) {
                // Delete old photo file if it exists
                $oldPath = $uploadDir . $recipe['photoFileName'];
                if (file_exists($oldPath)) @unlink($oldPath);
                $photoFileName = $newPhoto;
            } else {
                $errors[] = 'Failed to save the new photo.';
            }
        }
    }
 
    // ── Video: replace if new file/link provided, otherwise keep old ─────────
    $videoFilePath = $recipe['videoFilePath']; // default: keep old
    if (isset($_FILES['videoFile']) && $_FILES['videoFile']['error'] === UPLOAD_ERR_OK) {
        $allowedVid = ['video/mp4','video/quicktime','video/x-msvideo','video/webm'];
        $ext_vid_check = strtolower(pathinfo($_FILES['videoFile']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext_vid_check, ['mp4','mov','avi','webm'])) {
            $errors[] = 'Video must be MP4, MOV, AVI, or WEBM.';
        } else {
            $extV      = pathinfo($_FILES['videoFile']['name'], PATHINFO_EXTENSION);
            $newVideo  = uniqid('video_', true) . '.' . $extV;
            $uploadDirV = 'videos/';
            if (!is_dir($uploadDirV)) mkdir($uploadDirV, 0755, true);
            if (move_uploaded_file($_FILES['videoFile']['tmp_name'], $uploadDirV . $newVideo)) {
                // Delete old video file only if it was a local file (not a URL)
                if ($recipe['videoFilePath'] && !filter_var($recipe['videoFilePath'], FILTER_VALIDATE_URL)) {
                    $oldVidPath = $uploadDirV . $recipe['videoFilePath'];
                    if (file_exists($oldVidPath)) @unlink($oldVidPath);
                }
                $videoFilePath = $newVideo;
            } else {
                $errors[] = 'Failed to save the new video.';
            }
        }
    } elseif (!empty(trim($_POST['videoLink'] ?? ''))) {
        $videoFilePath = trim($_POST['videoLink']);
    }
 
    // ── Validate dynamic rows ─────────────────────────────────────────────────
    $ingredientNames = $_POST['ingredientName']     ?? [];
    $ingredientQtys  = $_POST['ingredientQuantity'] ?? [];
    $steps           = $_POST['step']               ?? [];
 
    if (empty($ingredientNames) || count(array_filter(array_map('trim', $ingredientNames))) === 0)
        $errors[] = 'Add at least one ingredient.';
    if (empty($steps) || count(array_filter(array_map('trim', $steps))) === 0)
        $errors[] = 'Add at least one instruction step.';
 
    // ── Commit to DB ──────────────────────────────────────────────────────────
    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Update Recipe row
            $upd = $conn->prepare(
                "UPDATE Recipe SET categoryID=?, NAME=?, description=?, photoFileName=?, videoFilePath=?
                 WHERE id=?"
            );
            $upd->bind_param('issssi', $categoryID, $name, $description, $photoFileName, $videoFilePath, $recipeID);
            $upd->execute();
            $upd->close();
 
            // Delete old ingredients & instructions, then re-insert
            $conn->query("DELETE FROM Ingredients WHERE recipeID = $recipeID");
            $conn->query("DELETE FROM Instructions WHERE recipeID = $recipeID");
 
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
            header('Location: myRecipes.php?edited=1');
            exit;
 
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
 
    // On error, update local vars so the form re-renders with user's input
    $recipe['NAME']          = $name;
    $recipe['categoryID']    = $categoryID;
    $recipe['description']   = $description;
    $recipe['photoFileName'] = $photoFileName;
    $recipe['videoFilePath'] = $videoFilePath;
 
    // Rebuild ingredient/instruction arrays from POST for re-rendering
    $ingRows  = [];
    foreach ($ingredientNames as $i => $n) {
        $ingRows[] = ['ingredientName' => $n, 'ingredientQuantity' => $ingredientQtys[$i] ?? ''];
    }
    $insRows = $steps;
}
?>
<!DOCTYPE html>
<html lang="en">
 
<head>
  <meta charset="UTF-8">
  <title>Edit Recipe – Palm Glow</title>
 
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600&display=swap"
    rel="stylesheet">
 
  <style>
    :root {
      --matcha: #809671; --almond: #E5E0D8; --pistachio: #B3B792;
      --chai: #D2AB80; --carob: #725C3A; --vanilla: #E5D2B8; --white: #FFFFFF;
      --ink: #2C2C2C; --border: rgba(114,92,58,.16);
      --r-lg: 20px; --r-md: 16px; --r-pill: 999px;
      --shadow: 0 16px 40px rgba(114,92,58,.12);
      --shadow-soft: 0 10px 24px rgba(114,92,58,.10);
      --container: 1100px;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0; font-family: Inter, system-ui, Arial, sans-serif;
      color: var(--ink);
      background: linear-gradient(180deg, var(--almond), rgba(229,224,216,.85));
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
    .nav-link:hover { background: rgba(255,255,255,.55); border-color: rgba(114,92,58,.12); opacity: 1; }
    .cta { display: flex; gap: 10px; align-items: center; }
    .btn {
      border: 1px solid rgba(114,92,58,.14); background: rgba(255,255,255,.60);
      padding: 10px 14px; border-radius: var(--r-pill); cursor: pointer;
      font-weight: 600; transition: .2s;
    }
    .btn:hover { transform: translateY(-1px); box-shadow: var(--shadow-soft); }
    .btn-primary { background: var(--matcha); border-color: rgba(128,150,113,.55); color: #fff; }
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
    label { display: block; margin-top: 18px; margin-bottom: 6px; font-size: 13px; font-weight: 500; opacity: .85; }
    input, textarea, select {
      width: 100%; padding: 11px 14px; font-size: 14px; border-radius: var(--r-pill);
      border: 1px solid var(--border); font-family: 'Inter', sans-serif; background: rgba(255,255,255,.85);
    }
    textarea { border-radius: 16px; resize: none; line-height: 1.6; }
    input:focus, textarea:focus, select:focus { outline: none; border-color: rgba(128,150,113,.5); }
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
    #currentPhotoContainer img {
      max-width: 180px; border-radius: 16px; margin-top: 6px; border: 1px solid var(--border);
    }
    #currentVideoContainer a { font-size: 13px; color: var(--matcha); text-decoration: none; opacity: .85; }
    .alert-error {
      background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c;
      border-radius: 12px; padding: 12px 16px; margin-bottom: 18px; font-size: 13.5px;
    }
    .alert-error ul { margin: 6px 0 0; padding-left: 18px; }
    .footer {
      margin-top: 28px; padding: 36px 0 18px;
      background: linear-gradient(180deg, rgba(210,171,128,.35), rgba(229,210,184,.55));
      border-top: 1px solid rgba(114,92,58,.12);
    }
    .footer-grid { display: grid; grid-template-columns: 1.4fr 1fr 1.1fr; gap: 18px; align-items: start; }
    .footer-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
    .footer-brand img {
      width: 44px; height: 44px; object-fit: contain; border-radius: 14px;
      border: 1px solid rgba(114,92,58,.12); background: rgba(255,255,255,.35);
    }
    .footer-brand b { color: var(--carob); font-size: 18px; }
    .footer-mini { font-size: 12px; opacity: .75; margin-top: 2px; }
    .footer-col h4 { margin: 6px 0 10px; font-family: "Cormorant Garamond", serif; font-size: 20px; color: var(--carob); }
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
    <h1>Edit Recipe</h1>
 
    <form method="POST" enctype="multipart/form-data" id="editForm"
          action="editRecipe.php?id=<?= $recipeID ?>">
 
      <?php if (!empty($errors)): ?>
        <div class="alert-error">
          <strong>Please fix the following:</strong>
          <ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
        </div>
      <?php endif; ?>
 
      <label>Recipe Name</label>
      <input type="text" name="name" placeholder="Recipe Name"
             value="<?= htmlspecialchars($recipe['NAME']) ?>" required>
 
      <label>Category</label>
      <select name="categoryID" required>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>"
            <?= ($recipe['categoryID'] == $cat['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['categoryName']) ?>
          </option>
        <?php endforeach; ?>
      </select>
 
      <label>Description</label>
      <textarea name="description" rows="3" required
      ><?= htmlspecialchars($recipe['description']) ?></textarea>
 
      <!-- Current photo preview -->
      <label>Current Photo</label>
      <div id="currentPhotoContainer">
        <img src="images/<?= htmlspecialchars($recipe['photoFileName']) ?>"
             alt="Current recipe photo"
             onerror="this.style.display='none'">
      </div>
 
      <label>Change Photo <span style="opacity:.6">(leave empty to keep current)</span></label>
      <input type="file" name="photo" id="photo" accept="image/*">
 
      <label>Ingredients</label>
      <div id="ingredients">
        <?php foreach ($ingRows as $ing): ?>
          <div class="row">
            <input type="text" name="ingredientName[]"
                   value="<?= htmlspecialchars($ing['ingredientName']) ?>" required>
            <input type="text" name="ingredientQuantity[]"
                   value="<?= htmlspecialchars($ing['ingredientQuantity']) ?>" required>
            <button type="button" onclick="removeRow(this)">✕</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="add-btn" onclick="addIngredient()">+ Add Ingredient</button>
 
      <label>Instructions</label>
      <div id="instructions">
        <?php foreach ($insRows as $step): ?>
          <div class="row">
            <input type="text" name="step[]"
                   value="<?= htmlspecialchars($step) ?>" required>
            <button type="button" onclick="removeRow(this)">✕</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="add-btn" onclick="addInstruction()">+ Add Step</button>
 
      <!-- Current video -->
      <label>Current Video</label>
      <div id="currentVideoContainer">
        <?php if ($recipe['videoFilePath']): ?>
          <?php if (filter_var($recipe['videoFilePath'], FILTER_VALIDATE_URL)): ?>
            <a href="<?= htmlspecialchars($recipe['videoFilePath']) ?>" target="_blank">Watch Current Video ↗</a>
          <?php else: ?>
            <a href="videos/<?= htmlspecialchars($recipe['videoFilePath']) ?>" target="_blank">Watch Current Video ↗</a>
          <?php endif; ?>
        <?php else: ?>
          <span style="opacity:.55;font-size:13px">No video attached</span>
        <?php endif; ?>
      </div>
 
      <label>Change Video <span style="opacity:.6">(optional – upload new file)</span></label>
      <input type="file" name="videoFile" id="videoFile" accept="video/*">
 
      <label>Or New Video Link</label>
      <input type="url" name="videoLink" id="videoLink" placeholder="https://youtube.com/..."
             value="<?= filter_var($recipe['videoFilePath'] ?? '', FILTER_VALIDATE_URL)
                        ? htmlspecialchars($recipe['videoFilePath']) : '' ?>">
 
      <button type="submit" class="submit-btn">Save Changes</button>
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
