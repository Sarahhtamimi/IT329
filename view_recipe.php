<?php
include 'auth_any.php';
include 'db_connection.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Recipe ID is missing.");
}

$recipeID = $_GET['id'];
$userID = $_SESSION['userID'];
$userType = $_SESSION['userType'];

$sqlRecipe = "SELECT Recipe.id, Recipe.userID, Recipe.categoryID,
                     Recipe.NAME AS recipeName,
                     Recipe.description,
                     Recipe.photoFileName,
                     Recipe.videoFilePath,
                     User.firstName, User.lastName,
                     User.photoFileName AS userPhoto,
                     RecipeCategory.categoryName
              FROM Recipe
              JOIN User ON Recipe.userID = User.id
              JOIN RecipeCategory ON Recipe.categoryID = RecipeCategory.id
              WHERE Recipe.id = ?";

$stmtRecipe = $conn->prepare($sqlRecipe);
$stmtRecipe->bind_param("i", $recipeID);
$stmtRecipe->execute();
$resultRecipe = $stmtRecipe->get_result();

if ($resultRecipe->num_rows == 0) {
    die("Recipe not found.");
}

$recipe = $resultRecipe->fetch_assoc();

$sqlIngredients = "SELECT * FROM Ingredients WHERE recipeID = ?";
$stmtIngredients = $conn->prepare($sqlIngredients);
$stmtIngredients->bind_param("i", $recipeID);
$stmtIngredients->execute();
$resultIngredients = $stmtIngredients->get_result();

$sqlInstructions = "SELECT * FROM Instructions WHERE recipeID = ? ORDER BY stepOrder ASC";
$stmtInstructions = $conn->prepare($sqlInstructions);
$stmtInstructions->bind_param("i", $recipeID);
$stmtInstructions->execute();
$resultInstructions = $stmtInstructions->get_result();

$sqlComments = "SELECT Comment.id, Comment.recipeID, Comment.userID,
                       Comment.COMMENT AS userComment,
                       Comment.DATE AS commentDate,
                       User.firstName, User.lastName
                FROM Comment
                JOIN User ON Comment.userID = User.id
                WHERE Comment.recipeID = ?
                ORDER BY Comment.DATE DESC";

$stmtComments = $conn->prepare($sqlComments);
$stmtComments->bind_param("i", $recipeID);
$stmtComments->execute();
$resultComments = $stmtComments->get_result();

$isCreator = ($recipe['userID'] == $userID);
$isAdmin = ($userType == 'admin');

$alreadyLiked = false;
$alreadyFavourite = false;
$alreadyReported = false;

if (!$isCreator && !$isAdmin) {
    $sqlLike = "SELECT * FROM Likes WHERE userID = ? AND recipeID = ?";
    $stmtLike = $conn->prepare($sqlLike);
    $stmtLike->bind_param("ii", $userID, $recipeID);
    $stmtLike->execute();
    $resultLike = $stmtLike->get_result();
    if ($resultLike->num_rows > 0) {
        $alreadyLiked = true;
    }

    $sqlFavourite = "SELECT * FROM Favourites WHERE userID = ? AND recipeID = ?";
    $stmtFavourite = $conn->prepare($sqlFavourite);
    $stmtFavourite->bind_param("ii", $userID, $recipeID);
    $stmtFavourite->execute();
    $resultFavourite = $stmtFavourite->get_result();
    if ($resultFavourite->num_rows > 0) {
        $alreadyFavourite = true;
    }

    $sqlReport = "SELECT * FROM Report WHERE userID = ? AND recipeID = ?";
    $stmtReport = $conn->prepare($sqlReport);
    $stmtReport->bind_param("ii", $userID, $recipeID);
    $stmtReport->execute();
    $resultReport = $stmtReport->get_result();
    if ($resultReport->num_rows > 0) {
        $alreadyReported = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Recipe</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    :root{
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

    *{ box-sizing:border-box; }
    a{ color:inherit; text-decoration:none; }

    body{
      margin:0;
      font-family: Inter, system-ui, Arial, sans-serif;
      color: var(--ink);
      background: linear-gradient(180deg, var(--almond), rgba(229, 224, 216, .85));
    }

    .pattern{
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

    .container{
      width: min(var(--container), calc(100% - 32px));
      margin: 0 auto;
    }

    header{
      position: sticky;
      top: 0;
      z-index: 100;
      background: rgba(229, 224, 216, .78);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(114, 92, 58, .10);
    }

    .nav-wrap{
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      align-items: center;
      gap: 16px;
      padding: 14px 0;
    }

    .brand{
      display:flex;
      align-items:center;
      gap:12px;
      min-width: 220px;
      justify-self: start;
    }

    .brand img{
      width:44px;
      height:44px;
      object-fit: contain;
      border-radius:12px;
      background: rgba(255,255,255,.35);
      border:1px solid rgba(114,92,58,.12);
      display:block;
    }

    .brand .name{
      font-family: "Cormorant Garamond", serif;
      font-weight:700;
      font-size:22px;
      color: var(--carob);
      line-height:1;
    }

    .brand .tag{
      font-size:12px;
      opacity:.75;
      margin-top:3px;
    }

    nav{
      justify-self: center;
      display:flex;
      gap:10px;
      flex-wrap: nowrap;
      justify-content:center;
      white-space: nowrap;
    }

    .nav-link{
      padding:10px 14px;
      border-radius:14px;
      border:1px solid transparent;
      font-size:13.5px;
      opacity:.86;
      transition:.2s;
    }

    .nav-link:hover{
      background: rgba(255,255,255,.55);
      border-color: rgba(114,92,58,.12);
      opacity: 1;
    }

    img{ max-width:100%; display:block; }

    .viewRecipe_header{
      position:relative;
      width:100%;
      min-height:450px;
      overflow:hidden;
      border-radius:0 0 24px 24px;
      box-shadow:0 10px 24px rgba(114,92,58,.10);
    }

    .viewRecipe_headerImg{
      width:100%;
      height:100%;
      object-fit:cover;
      position:absolute;
      inset: 0;
    }

    .viewRecipe_headerTitle{
      position:relative;
      padding:44px 44px 56px;
      max-width:820px;
    }

    .viewRecipe_headerTitle h1{
      font-family: "Cormorant Garamond", serif;
      font-weight:700;
      margin:0;
      color:#fff;
      font-size:54px;
      line-height:1.05;
      text-shadow:0 10px 30px rgba(0,0,0,.25);
    }

    .viewRecipe_metaCard{
      margin:26px auto;
      width:1100px;
      max-width: calc(100% - 32px);
      background:rgba(255,255,255,.70);
      border:1px solid rgba(114,92,58,.12);
      border-radius:20px;
      box-shadow:0 10px 24px rgba(114,92,58,.10);
      padding:18px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 14px;
    }

    .viewRecipe_author{
      display:flex;
      gap:12px;
      align-items:center;
      min-width:240px;
    }

    .viewRecipe_avatar{
      width:48px;
      height:48px;
      border-radius:50%;
      overflow:hidden;
      border:1px solid rgba(114,92,58,.12);
      object-fit:cover;
    }

    .viewRecipe_author small{
      display:block;
      opacity:.82;
      font-size:12px;
      letter-spacing:.12em;
      text-transform:uppercase;
      margin-bottom:2px;
    }

    .viewRecipe_author strong{ font-weight:600; }

    .viewRecipe_buttons{
      display:flex;
      gap:10px;
      flex-wrap: wrap;
    }

    .viewRecipe_btn{
      border:1px solid rgba(114,92,58,.12);
      background:rgba(255,255,255,.80);
      border-radius:999px;
      padding:10px 14px;
      display:inline-flex;
      align-items:center;
      gap:8px;
      font-family: Inter;
      color:#2C2C2C;
      cursor:pointer;
      transition: .2s;
    }

    .viewRecipe_btn:hover{ transform: translateY(-1px); box-shadow: var(--shadow-soft); }

    .info{
      cursor:default;
      background: rgba(128,150,113,.16);
      color: var(--matcha);
      border:1px solid rgba(128,150,113,.22);
    }
    .info:hover{ transform:none; box-shadow:none; }

    .viewRecipe_icon{ width:17px; text-align:center; opacity:.75; }
    .flag{ width:14.5px; }

    .categories{
      width:1100px;
      max-width: calc(100% - 32px);
      margin: 10px auto 0;
    }

    .viewRecipe_description{
      margin:14px auto 0;
      width:1100px;
      max-width: calc(100% - 32px);
      opacity:.85;
      line-height: 1.75;
    }

    .viewRecipe_2Cards{
      margin: 30px auto;
      width: 1100px;
      max-width: calc(100% - 32px);
      display: flex;
      gap: 20px;
      padding-top: 26px;
      border-top:1px solid rgba(114,92,58,.10);
      align-items: flex-start;
    }

    .viewRecipe_metaCard.ingredients{
      width: 100%;
      background: rgba(255,255,255,.70);
      border:1px solid rgba(114,92,58,.12);
      border-radius: 20px;
      padding: 18px;
      box-shadow: 0 10px 24px rgba(114,92,58,.10);
      margin: 0;
      display: block;
    }

    .ingredients h2{
      font-family: "Cormorant Garamond", serif;
      font-size: 34px;
      font-weight: 700;
      margin: 0 0 12px;
      color: var(--carob);
    }

    .ingredients ul{ padding-left: 20px; margin: 0; }
    .ingredients ul li{
      margin-bottom: 12px;
      font-size: 15px;
      font-weight: 500;
      list-style-type: disc;
      color: #2C2C2C;
    }
    .ingredients ul li::marker{ color: var(--matcha); }

    .ingredients span{
      font-size: 14px;
      font-weight: 400;
      margin-left: 6px;
      opacity: .82;
    }

    .instructions{ font-weight: 600; }
    .instructions p{
      margin: 0;
      font-weight: 400;
      color: #2C2C2C;
      line-height: 1.75;
      opacity: .90;
    }

    .instructions ol{ list-style: none; padding-left: 0; margin: 0; }
    .instructions ol li{
      display: flex;
      gap: 18px;
      align-items: flex-start;
      margin-bottom: 22px;
    }

    .stepNum{
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: rgba(229,224,216,.35);
      border: 1px solid rgba(114,92,58,.12);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      color: #2C2C2C;
      flex-shrink: 0;
    }

    .viewRecipe_section{
      width: 1100px;
      max-width: calc(100% - 32px);
      margin: 26px auto;
    }

    .viewRecipe_section h3{
      font-family: "Cormorant Garamond", serif;
      font-weight: 700;
      font-size: 28px;
      margin: 0 0 14px;
      color: var(--carob);
    }

    .viewRecipe_videoWrap{
      border-radius: 20px;
      overflow: hidden;
      background: #1f1f1f;
      border: 1px solid rgba(114,92,58,.12);
      box-shadow: 0 10px 24px rgba(114,92,58,.10);
      margin-bottom: 40px;
    }

    .viewRecipe_videoWrap video{
      width: 100%;
      height: 520px;
      border: 0;
      display: block;
    }

    .viewRecipe_commentsCard{
      background: rgba(255,255,255,.70);
      border: 1px solid rgba(114,92,58,.12);
      border-radius: 20px;
      box-shadow: 0 10px 24px rgba(114,92,58,.10);
      overflow: hidden;
    }

    .viewRecipe_commentList{ margin: 0; padding: 0; list-style: none; }
    .viewRecipe_comment{
      padding: 22px 26px;
      border-bottom: 1px solid rgba(114,92,58,.10);
    }
    .viewRecipe_comment:last-child{ border-bottom: 0; }

    .viewRecipe_commentTop{
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 8px;
    }

    .viewRecipe_badge{
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: rgba(229,224,216,.35);
      border: 1px solid rgba(114,92,58,.12);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      color: #2C2C2C;
      font-size: 12px;
    }

    .viewRecipe_commentTop b{ font-weight: 600; font-size: 15px; }
    .viewRecipe_commentTop small{ opacity: .82; font-size: 12px; margin-left: 8px; }

    .viewRecipe_comment p{
      margin: 0;
      font-size: 14px;
      line-height: 1.6;
      max-width: 950px;
      opacity: .90;
    }

    .viewRecipe_commentForm textarea{
      width: calc(100% - 52px);
      margin: 22px 26px;
      min-height: 86px;
      resize: none;
      border-radius: 16px;
      border: 1px solid rgba(114,92,58,.12);
      background: rgba(255,255,255,.80);
      padding: 14px;
      font-family: Inter;
      color: #2C2C2C;
      outline: none;
    }
    .viewRecipe_commentForm textarea:focus{ border-color: rgba(128,150,113,.55); }

    .viewRecipe_row{ display:flex; justify-content: flex-end; }

    .viewRecipe_postBtn{
      background: var(--matcha);
      color: #fff;
      border: 1px solid rgba(128,150,113,.35);
      border-radius: 999px;
      padding: 10px 20px;
      margin-right:26px;
      margin-bottom: 26px;
      font-weight: 600;
      cursor: pointer;
      transition: .2s;
    }
    .viewRecipe_postBtn:hover{ opacity: .93; transform: translateY(-1px); box-shadow: var(--shadow-soft); }

    #commentTitle{
      margin:22px 26px;
      font-weight: 700;
      font-size: 28px;
      font-family: "Cormorant Garamond", serif;
      color: var(--carob);
    }

    .footer{
      margin-top: 28px;
      padding: 36px 0 18px;
      background: linear-gradient(180deg, rgba(210, 171, 128, .35), rgba(229, 210, 184, .55));
      border-top: 1px solid rgba(114, 92, 58, .12);
    }

    .footer-grid{
      display: grid;
      grid-template-columns: 1.4fr 1fr 1.1fr;
      gap: 18px;
      align-items: start;
    }

    .footer-brand{
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 10px;
    }

    .footer-brand img{
      width: 44px;
      height: 44px;
      object-fit: contain;
      border-radius: 14px;
      border: 1px solid rgba(114, 92, 58, .12);
      background: rgba(255, 255, 255, .35);
    }

    .footer-brand b{
      color: var(--carob);
      font-size: 18px;
    }

    .footer-mini{
      font-size: 12px;
      opacity: .75;
      margin-top: 2px;
    }

    .footer-col h4{
      margin: 6px 0 10px;
      font-family: "Cormorant Garamond", serif;
      font-size: 20px;
      color: var(--carob);
    }

    .footer-text{
      margin: 0;
      font-size: 13.5px;
      line-height: 1.7;
      opacity: .82;
      max-width: 52ch;
    }

    .footer-text.small{
      font-size: 12.8px;
      opacity: .78;
      margin-bottom: 10px;
    }

    .footer-links{
      display: flex;
      flex-direction: column;
      gap: 8px;
      font-size: 13px;
      opacity: .82;
    }

    .subscribe{
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .subscribe input{
      flex: 1 1 180px;
      padding: 12px 14px;
      border-radius: var(--r-pill);
      border: 1px solid rgba(114, 92, 58, .14);
      background: rgba(255, 255, 255, .70);
      outline: none;
    }

    .subscribe input:focus{ border-color: rgba(128, 150, 113, .55); }

    .subscribe button{
      padding: 12px 16px;
      border-radius: var(--r-pill);
      border: 1px solid rgba(128, 150, 113, .55);
      background: var(--matcha);
      color: #fff;
      cursor: pointer;
      font-weight: 600;
    }

    .subscribe button:hover{ opacity: .93; }

    .sub-note{
      margin-top: 10px;
      font-size: 12.5px;
      color: var(--carob);
      opacity: .9;
      min-height: 16px;
    }

    .footer-bottom{
      margin-top: 18px;
      padding-top: 14px;
      border-top: 1px solid rgba(114, 92, 58, .10);
      display: flex;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      font-size: 12.5px;
      opacity: .84;
    }
    .viewRecipe_btn[disabled]{
      opacity: .65;
      cursor: not-allowed;
      background: #d9d9d9;
      color: #666;
      border-color: #c9c9c9;
      box-shadow: none;
    }

    .viewRecipe_btn[disabled]:hover{
      transform: none;
      box-shadow: none;
    }
    @media (max-width: 980px){
      nav{ display:none; }
      .footer-grid{ grid-template-columns: 1fr; }
      .footer-text{ max-width: 100%; }
      .viewRecipe_header{ min-height: 320px; }
      .viewRecipe_headerTitle{ padding: 26px; }
      .viewRecipe_headerTitle h1{ font-size: 34px; }
      .viewRecipe_2Cards{ flex-direction: column; }
      .viewRecipe_videoWrap video{ height: 320px; }
    }

    @media (max-width: 768px){
      .viewRecipe_metaCard{ flex-direction: column; align-items: flex-start; }
      .viewRecipe_videoWrap video{ height: 260px; }
      .viewRecipe_comment{ padding: 18px; }
      .viewRecipe_comment p{ font-size: 13.5px; }
    }
  </style>
</head>

<body>
  <div class="pattern" aria-hidden="true"></div>

  <header>
    <div class="container nav-wrap">
      <a class="brand" href="index.php">
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

  <section class="viewRecipe_header">
    <img class="viewRecipe_headerImg" src="images/<?php echo $recipe['photoFileName']; ?>" alt="Recipe cover">
    <div class="viewRecipe_headerTitle">
      <h1><?php echo $recipe['recipeName']; ?></h1>
    </div>
  </section>

  <main>
    <section class="viewRecipe_metaCard">
      <div class="viewRecipe_author">
        <img class="viewRecipe_avatar" src="images/<?php echo $recipe['userPhoto']; ?>" alt="Author">
        <div>
          <small>Recipe by</small>
          <strong><?php echo $recipe['firstName'] . " " . $recipe['lastName']; ?></strong>
        </div>
      </div>

      <?php if (!$isCreator && !$isAdmin) { ?>
      <div class="viewRecipe_buttons">

        <?php if ($alreadyFavourite) { ?>
        <button class="viewRecipe_btn" type="button" disabled>
          <span class="viewRecipe_icon"><img src="images/ribbon.png" alt="bookmark"></span>
          Saved
        </button>
        <?php } else { ?>
          <a class="viewRecipe_btn" href="add_favourite.php?id=<?php echo $recipeID; ?>">
            <span class="viewRecipe_icon"><img src="images/ribbon.png" alt="bookmark"></span>
            Save
          </a>
        <?php } ?>

        <?php if ($alreadyLiked) { ?>
          <button class="viewRecipe_btn" type="button" disabled>
            <span class="viewRecipe_icon"><img src="images/heart2.png" alt="like"></span>
            Liked
          </button>
        <?php } else { ?>
          <a class="viewRecipe_btn" href="add_like.php?id=<?php echo $recipeID; ?>">
            <span class="viewRecipe_icon"><img src="images/heart2.png" alt="like"></span>
            Like
          </a>
        <?php } ?>

        <?php if ($alreadyReported) { ?>
          <button class="viewRecipe_btn" type="button" disabled>
            <span class="viewRecipe_icon flag"><img src="images/report.png" alt="report"></span>
            Reported
          </button>
        <?php } else { ?>
          <a class="viewRecipe_btn" href="add_report.php?id=<?php echo $recipeID; ?>">
            <span class="viewRecipe_icon flag"><img src="images/report.png" alt="report"></span>
            Report
          </a>
        <?php } ?>

      </div>
      <?php } ?>
    </section>

    <div class="viewRecipe_buttons categories">
      <div class="viewRecipe_btn info"><?php echo $recipe['categoryName']; ?></div>
    </div>

    <p class="viewRecipe_description">
      <?php echo $recipe['description']; ?>
    </p>

    <section class="viewRecipe_2Cards">
      <div class="viewRecipe_metaCard ingredients">
        <h2>Ingredients</h2>
        <ul>
          <?php while ($ingredient = $resultIngredients->fetch_assoc()) { ?>
            <li><?php echo $ingredient['ingredientName']; ?><span><?php echo $ingredient['ingredientQuantity']; ?></span></li>
          <?php } ?>
        </ul>
      </div>

      <div class="viewRecipe_metaCard ingredients instructions">
        <h2>Instructions</h2>
        <ol>
          <?php while ($instruction = $resultInstructions->fetch_assoc()) { ?>
            <li>
              <span class="stepNum"><?php echo $instruction['stepOrder']; ?></span>
              <p><?php echo $instruction['step']; ?></p>
            </li>
          <?php } ?>
        </ol>
      </div>
    </section>

    <?php if (!empty($recipe['videoFilePath'])) { ?>
    <section class="viewRecipe_section">
      <h3>Watch the Recipe</h3>
      <div class="viewRecipe_videoWrap">
        <video controls>
            <source src="videos/<?php echo $recipe['videoFilePath']; ?>" type="video/mp4">        </video>
      </div>
    </section>
    <?php } ?>

    <section class="viewRecipe_section viewRecipe_commentsCard">
      <div class="viewRecipe_commentsHead">
        <h3 id="commentTitle">Comments</h3>
      </div>

      <ul class="viewRecipe_commentList">
        <?php
        if ($resultComments->num_rows > 0) {
            while ($comment = $resultComments->fetch_assoc()) {
                $initial = strtoupper(substr($comment['firstName'], 0, 1));
        ?>
        <li class="viewRecipe_comment">
          <div class="viewRecipe_commentTop">
            <div class="viewRecipe_badge"><?php echo $initial; ?></div>
            <div>
              <b><?php echo $comment['firstName'] . " " . $comment['lastName']; ?></b>
              <small><?php echo $comment['commentDate']; ?></small>
            </div>
          </div>
          <p><?php echo $comment['userComment']; ?></p>
        </li>
        <?php
            }
        } else {
            echo '<li class="viewRecipe_comment"><p>No comments yet.</p></li>';
        }
        ?>
      </ul>

      
      <form class="viewRecipe_commentForm" action="add_comment.php" method="post">
        <input type="hidden" name="recipeID" value="<?php echo $recipeID; ?>">
        <textarea name="comment" placeholder="Share your thoughts on this recipe..." required></textarea>
        <div class="viewRecipe_row">
          <button class="viewRecipe_postBtn" type="submit">Post Comment</button>
        </div>
      </form>
      
    </section>
  </main>

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
    </div>
  </footer>
</body>
</html>