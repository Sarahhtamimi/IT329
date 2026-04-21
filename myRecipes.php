<?php
// Feature 1: User Authentication
include 'auth_user.php';
include 'db_connection.php';

//Get the user id:
$userID = $_SESSION['userID'];

// Feature 2: Retrieve User Recipes and Likes Count
$sql = "
    SELECT
        r.id,
        r.NAME,
        r.photoFileName,
        r.videoFilePath,
        COUNT(l.recipeID) AS likeCount 
    FROM Recipe r
    LEFT JOIN Likes l ON r.id = l.recipeID
    WHERE r.userID = ?
    GROUP BY r.id
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();

$result = $stmt->get_result();


//Ingredients and put it in array:
$ingredientsByRecipe = [];

$sqlIng = "
    SELECT recipeID, ingredientName, ingredientQuantity
    FROM ingredients
";

$stmtIng = $conn->prepare($sqlIng);
$stmtIng->execute();
$ingResult = $stmtIng->get_result();

//loop:
while ($ing = $ingResult->fetch_assoc()) {
    $ingredientsByRecipe[$ing['recipeID']][] = $ing;
}

//Instructions and put it in array:
$instructionsByRecipe = [];

$sqlIns = "
    SELECT recipeID, step, stepOrder
    FROM instructions
    ORDER BY recipeID, stepOrder
";

$stmtIns = $conn->prepare($sqlIns);
$stmtIns->execute();
$insResult = $stmtIns->get_result();

while ($ins = $insResult->fetch_assoc()) {
    $instructionsByRecipe[$ins['recipeID']][] = $ins;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet">

  <title>Palm Glow — My Recipes</title>

  <style>
    :root {
      --almond: #E5E0D8;
      --vanilla: #E5D2B8;
      --matcha: #809671;
      --pistachio: #B3B792;
      --chai: #D2AB80;
      --carob: #725C3A;
      --white: #FFFFFF;

      --ink: #2C2C2C;
      --border: rgba(114, 92, 58, .12);
      --divider: rgba(114, 92, 58, .10);

      --shadow-soft: 0 10px 24px rgba(114, 92, 58, .10);
      --shadow-normal: 0 16px 40px rgba(114, 92, 58, .12);

      --r-lg: 20px;
      --r-md: 16px;
      --r-pill: 999px;
      --container: 1100px;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: Inter, system-ui, Arial, sans-serif;
      color: var(--ink);
      background:
        linear-gradient(180deg, var(--almond), rgba(229, 224, 216, .85));
    }

    /* pattern like your home page */
    .pattern {
      position: fixed;
      inset: 0;
      pointer-events: none;
      opacity: .09;
      background-image:
        linear-gradient(45deg, rgba(179, 183, 146, .35) 25%, transparent 25%),
        linear-gradient(-45deg, rgba(179, 183, 146, .35) 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, rgba(179, 183, 146, .35) 75%),
        linear-gradient(-45deg, transparent 75%, rgba(179, 183, 146, .35) 75%);
      background-size: 34px 34px;
      z-index: -2;
    }

    a {
      color: inherit;
      text-decoration: none;
    }

    .container {
      width: min(var(--container), calc(100% - 32px));
      margin: 0 auto;
    }

    /* ============ HEADER (same vibe) ============ */
    header {
      position: sticky;
      top: 0;
      z-index: 10;
      background: rgba(229, 224, 216, .78);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid var(--divider);
    }

    .nav-wrap {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 14px 0;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 220px;
    }

    .brand img {
      width: 44px;
      height: 44px;
      object-fit: contain;
      border-radius: 12px;
      background: rgba(255, 255, 255, .35);
      border: 1px solid var(--border);
    }

    .brand .name {
      font-family: "Cormorant Garamond", serif;
      font-weight: 700;
      font-size: 22px;
      color: var(--carob);
      line-height: 1;
    }

    .brand .tag {
      font-size: 12px;
      opacity: .75;
      margin-top: 3px;
    }

    nav {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      justify-content: center;
    }

    .nav-link {
      padding: 10px 14px;
      border-radius: 14px;
      border: 1px solid transparent;
      font-size: 13.5px;
      opacity: .86;
      transition: .2s;
    }

    .nav-link:hover {
      background: rgba(255, 255, 255, .55);
      border-color: var(--border);
      opacity: 1;
    }

    .cta {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .btn {
      border: 1px solid rgba(114, 92, 58, .14);
      background: rgba(255, 255, 255, .60);
      padding: 10px 14px;
      border-radius: var(--r-pill);
      cursor: pointer;
      font-weight: 600;
      transition: .2s;
    }

    .btn:hover {
      transform: translateY(-1px);
      box-shadow: var(--shadow-soft);
    }

    .btn-primary {
      background: var(--matcha);
      border-color: rgba(128, 150, 113, .55);
      color: #fff;
    }

    /* ============ PAGE ============ */
    main {
      padding: 28px 0 10px;
    }

    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin: 8px 0 16px;
    }

    .title {
      margin: 0;
      font-family: "Cormorant Garamond", serif;
      font-size: 32px;
      font-weight: 700;
      color: var(--carob);
      letter-spacing: .2px;
    }

    .btn-add {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 11px 16px;
      border-radius: var(--r-pill);
      font-weight: 700;
      background: var(--matcha);
      color: #fff;
      border: 1px solid rgba(128, 150, 113, .30);
      box-shadow: var(--shadow-soft);
      transition: transform .14s ease, filter .14s ease;
      white-space: nowrap;
    }

    .btn-add:hover {
      transform: translateY(-1px);
      filter: brightness(.98);
    }

    .card {
      background: rgba(255, 255, 255, .70);
      border: 1px solid var(--border);
      border-radius: 20px;
      box-shadow: var(--shadow-normal);
      overflow: hidden;
      backdrop-filter: blur(10px);
    }

    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }

    thead th {
      background: rgba(229, 210, 184, .55);
      color: var(--carob);
      padding: 16px 18px;
      text-align: left;
      font-weight: 700;
      font-size: 13.5px;
      border-bottom: 1px solid var(--divider);
      vertical-align: top;
    }

    tbody td {
      padding: 16px 18px;
      border-bottom: 1px solid var(--divider);
      font-size: 14px;
      vertical-align: top;
    }

    tbody tr:last-child td {
      border-bottom: none;
    }

    /* row hover = كشخة */
    tbody tr {
      position: relative;
      transition: transform .16s ease, background .16s ease, box-shadow .16s ease;
    }

    tbody tr:hover {
      background: rgba(229, 224, 216, .35);
      transform: translateY(-2px);
      box-shadow: 0 14px 34px rgba(114, 92, 58, .12);
    }

    tbody tr::after {
      content: "";
      position: absolute;
      inset: 0;
      pointer-events: none;
      opacity: 0;
      transform: translateX(-40%);
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .33), transparent);
      transition: opacity .16s ease, transform .55s ease;
    }

    tbody tr:hover::after {
      opacity: 1;
      transform: translateX(40%);
    }

    /* recipe cell = thumb + name link */
    .recipe-link {
      display: flex;
      align-items: center;
      gap: 12px;
      color: inherit;
      text-decoration: none;
    }

    .recipe-thumb {
      width: 44px;
      height: 44px;
      border-radius: 14px;
      object-fit: cover;
      border: 1px solid var(--border);
      background: rgba(229, 210, 184, .35);
      flex-shrink: 0;
      display: block;
    }

    .recipe-name {
      font-weight: 800;
      color: var(--carob);
    }

    .recipe-link:hover .recipe-name {
      text-decoration: underline;
      text-underline-offset: 3px;
    }

    .table-list {
      margin: 0;
      padding-left: 18px;
      font-size: 13.5px;
      line-height: 1.6;
    }

    .table-list li {
      margin-bottom: 4px;
    }

    .table-list li::marker {
      color: var(--matcha);
    }

    .link {
      color: var(--matcha);
      font-weight: 800;
      text-decoration: none;
    }

    .link:hover {
      text-decoration: underline;
    }

    .no-video {
      font-size: 12.5px;
      padding: 6px 10px;
      border-radius: var(--r-pill);
      background: rgba(229, 224, 216, .45);
      color: var(--carob);
      border: 1px solid var(--border);
      display: inline-block;
      font-weight: 700;
      white-space: nowrap;
    }

    .likes {
      text-align: center;
      font-weight: 800;
      color: var(--carob);
    }

    .pill {
      display: inline-block;
      padding: 8px 12px;
      border-radius: var(--r-pill);
      font-size: 12.5px;
      font-weight: 800;
      text-decoration: none;
      border: 1px solid var(--border);
      transition: transform .12s ease, filter .12s ease;
      white-space: nowrap;
      text-align: center;
    }

    .pill.view {
      background: rgba(128, 150, 113, .16);
      color: var(--carob);
    }

    .pill.delete {
      background: rgba(210, 171, 128, .22);
      color: var(--carob);
    }

    .pill:hover {
      transform: translateY(-1px);
      filter: brightness(.98);
    }

    /* ============ FOOTER ============ */
    .footer {
      margin-top: 28px;
      padding: 36px 0 18px;
      background: linear-gradient(180deg, rgba(210, 171, 128, .35), rgba(229, 210, 184, .55));
      border-top: 1px solid var(--border);
    }

    .footer-grid {
      display: grid;
      grid-template-columns: 1.4fr 1fr 1.1fr;
      gap: 18px;
      align-items: start;
    }

    .footer-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 10px;
    }

    .footer-brand img {
      width: 44px;
      height: 44px;
      object-fit: contain;
      border-radius: 14px;
      border: 1px solid var(--border);
      background: rgba(255, 255, 255, .35);
    }

    .footer-brand b {
      color: var(--carob);
      font-size: 18px;
    }

    .footer-mini {
      font-size: 12px;
      opacity: .75;
      margin-top: 2px;
    }

    .footer-col h4 {
      margin: 6px 0 10px;
      font-family: "Cormorant Garamond", serif;
      font-size: 20px;
      color: var(--carob);
    }

    .footer-text {
      margin: 0;
      font-size: 13.5px;
      line-height: 1.7;
      opacity: .82;
      max-width: 52ch;
    }

    .footer-text.small {
      font-size: 12.8px;
      opacity: .78;
      margin-bottom: 10px;
    }

    .footer-links {
      display: flex;
      flex-direction: column;
      gap: 8px;
      font-size: 13px;
      opacity: .82;
    }

    .subscribe {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .subscribe input {
      flex: 1 1 180px;
      padding: 12px 14px;
      border-radius: var(--r-pill);
      border: 1px solid rgba(114, 92, 58, .14);
      background: rgba(255, 255, 255, .70);
      outline: none;
      font-family: Inter, system-ui, Arial, sans-serif;
    }

    .subscribe input:focus {
      border-color: rgba(128, 150, 113, .55);
    }

    .subscribe button {
      padding: 12px 16px;
      border-radius: var(--r-pill);
      border: 1px solid rgba(128, 150, 113, .55);
      background: var(--matcha);
      color: #fff;
      cursor: pointer;
      font-weight: 700;
    }

    .subscribe button:hover {
      opacity: .93;
    }

    .sub-note {
      margin-top: 10px;
      font-size: 12.5px;
      color: var(--carob);
      opacity: .9;
      min-height: 16px;
    }

    .footer-bottom {
      margin-top: 18px;
      padding-top: 14px;
      border-top: 1px solid var(--divider);
      display: flex;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      font-size: 12.5px;
      opacity: .84;
    }

    @media (max-width: 980px) {
      nav {
        display: none;
      }

      .footer-grid {
        grid-template-columns: 1fr;
      }

      thead th,
      tbody td {
        padding: 14px 12px;
      }

      .title {
        font-size: 28px;
      }
    }

    .nav-wrap {
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      /* left / center / right */
      align-items: center;
      gap: 16px;
      padding: 14px 0;
    }

    .brand {
      justify-self: start;
      min-width: 220px;
    }

    nav {
      justify-self: center;
      display: flex;
      gap: 10px;
      flex-wrap: nowrap;
      justify-content: center;
      white-space: nowrap;
    }

    .cta {
      justify-self: end;
      display: flex;
      gap: 10px;
      align-items: center;
    }
  </style>
</head>

<body>
  <div class="pattern" aria-hidden="true"></div>

  <!-- Header -->
  <header>
    <div class="container nav-wrap">
      <a class="brand" href="home.html">
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

  <!-- Page -->
  <main>
    <div class="container">
      <div class="topbar">
        <h2 class="title">My Recipes</h2>
        <a href="addRecipe.php" class="btn-add">Add New Recipe</a>
      </div>

      <div class="card">
          <?php if ($result->num_rows > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Recipe</th>
              <th>Ingredients</th>
              <th>Instructions</th>
              <th>Video</th>
              <th>Likes</th>
              <th>Edit</th>
              <th>Delete</th>
            </tr>
          </thead>

          <tbody>
            <!--//Loop to fill all the table -->
            <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
<tr>
  <td>
    <a href="view_recipe.php?id=<?= $row['id'] ?>" class="recipe-link">
     <img src="images/<?= $row['photoFileName'] ?>" class="recipe-thumb" alt="Recipe image">
     <span class="recipe-name"> <?= $row['NAME'] ?></span>
    </a>
  </td>

  <td>
    <ul class="table-list">
        <?php if (isset($ingredientsByRecipe[$row['id']])): ?>
            
            <?php foreach ($ingredientsByRecipe[$row['id']] as $ing): ?>

                <li>
                    <?= $ing['ingredientName'] ?>
                    <span style="opacity:.82"> — <?= $ing['ingredientQuantity'] ?></span>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li>
                <span style="opacity:.82">No ingredients</span>
            </li>
        <?php endif; ?>
    </ul>
</td>
<td>
    <ol class="table-list">

        <?php if (isset($instructionsByRecipe[$row['id']])): ?>

            <?php foreach ($instructionsByRecipe[$row['id']] as $step): ?>

                <li>
                    <?= $step['step'] ?>
                </li>

            <?php endforeach; ?>

        <?php else: ?>

            <li>
                <span style="opacity:.82">No instructions</span>
            </li>

        <?php endif; ?>

    </ol>
</td>
    <td>
<?php
$videoPath = "videos/" . $row['videoFilePath'];

if (!empty($row['videoFilePath']) && file_exists($videoPath)):
?>
    <a 
        href="<?= $videoPath ?>" 
        class="link" 
        target="_blank"
    >
       <span class="no-video">Watch video</span>
    </a>
<?php else: ?>
    <span class="no-video">No video</span>
<?php endif; ?>
</td>
<td class="likes">
      <?= $row['likeCount'] ?>
  </td>


  <td>
   <a href="editRecipe.php?id=<?= $row['id'] ?>" class="pill view">Edit</a>
  </td>

  <td>
      <a href="delete_recipe_process.php?id=<?= $row['id'] ?>" class="pill delete">Delete</a>

  </td>
</tr>

<?php endwhile; ?>
<?php endif; ?>
          </tbody>
        </table>
      </div>
        <?php else: ?>
        <div class="card" style="padding:40px; text-align:center;">
  <p style="font-weight:700;">
    You have not added any recipes yet 🍽️
  </p>
</div>
        <?php endif; ?>
    </div>
  </main>

  <!-- Footer -->
  <footer class="footer">
    <div class="container footer-grid">
      <div class="footer-about">
        <div class="footer-brand">
          <img src="images/palmLogo.png" alt="Palm Glow Logo">
          <div>
            <b>Palm Glow</b>
            <div class="footer-mini">clean wellness • warm Saudi touch</div>
          </div>
        </div>
        <p class="footer-text">
          Save your favorites, explore quick ideas, and add your own creations — all in one warm vibe.
        </p>
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
        <p class="footer-text small">Get new recipes & wellness picks</p>

        <form class="subscribe" id="subscribeForm">
          <input type="email" id="subEmail" placeholder="Enter your email" required>
          <button type="submit">Subscribe</button>
        </form>

        <div class="sub-note" id="subNote" aria-live="polite"></div>
      </div>
    </div>

    <div class="container footer-bottom">
      <span>© 2026 Palm Glow</span>
      <span class="made">Made with Matcha ✦</span>
    </div>
  </footer>

  <script>
    document.querySelectorAll(".btn-delete").forEach(btn => {
      btn.addEventListener("click", () => {
        const row = btn.closest("tr");
        if (confirm("Delete this recipe?")) {
          row.remove();
        }
      });
    });
  </script>
  <script>
    const form = document.getElementById("subscribeForm");
    const note = document.getElementById("subNote");
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      note.textContent = "Subscribed successfully ✨";
      form.reset();
    });
  </script>






</body>

</html>
