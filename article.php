
<?php
require_once __DIR__ . "/config/db.php";

function e($value)
{
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

// Get article ID from URL
$articleId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$articleId || $articleId < 1) {
    http_response_code(404);
    exit("Invalid article.");
}

// Fetch only approved articles
$sql = "SELECT
            a.id,
            a.title,
            a.summary,
            a.content,
            a.image_url,
            a.created_at,
            a.is_breaking,
            c.name AS category_name,
            u.name AS author_name
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN users u ON a.user_id = u.id
        WHERE a.id = :id AND a.status = 'approved'
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute(["id" => $articleId]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    exit("Article not found or not available.");
}

// Increase article view count
$viewStmt = $pdo->prepare(
    "UPDATE articles SET views_count = views_count + 1 WHERE id = :id"
);
$viewStmt->execute(["id" => $articleId]);

$imagePath = $article["image_url"] ?: "images/default_news.jpg";

$publishedDate = date(
    "F j, Y",
    strtotime($article["created_at"])
);

$author = $article["author_name"] ?: "NSBM NewsDesk";
$category = $article["category_name"] ?: "General";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo e($article["title"]); ?> | NSBM NewsDesk</title>

    <link rel="stylesheet" href="index-style.css">

    <style>
        .article-page {
            max-width: 900px;
            margin: 50px auto;
            padding: 0 20px;
        }

        .article-back {
            display: inline-block;
            margin-bottom: 25px;
            color: #006b3c;
            text-decoration: none;
            font-weight: 600;
        }

        .article-category {
            display: inline-block;
            background: #e5f5eb;
            color: #006b3c;
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
        }

        .article-title {
            font-size: clamp(28px, 5vw, 42px);
            line-height: 1.25;
            margin: 20px 0;
            color: #17251e;
        }

        .article-meta {
            color: #718078;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .article-image {
            width: 100%;
            max-height: 480px;
            object-fit: cover;
            border-radius: 16px;
            margin-bottom: 30px;
        }

        .article-summary {
            font-size: 19px;
            line-height: 1.7;
            font-weight: 500;
            color: #53645a;
            margin-bottom: 25px;
        }

        .article-content {
            font-size: 17px;
            line-height: 1.9;
            color: #34443b;
            overflow-wrap: anywhere;
        }

        .article-content p {
            margin-bottom: 20px;
        }

        .article-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e0e8e3;
        }

        @media (max-width: 600px) {
            .article-page {
                margin: 25px auto;
            }

            .article-title {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>

    <main class="article-page">

        <a href="index.php" class="article-back">
            &larr; Back to NewsDesk
        </a>

        <article>

            <span class="article-category">
                <?php echo e($category); ?>
            </span>

            <?php if ($article["is_breaking"]): ?>
                <span class="article-category">Breaking News</span>
            <?php endif; ?>

            <h1 class="article-title">
                <?php echo e($article["title"]); ?>
            </h1>

            <div class="article-meta">
                By <?php echo e($author); ?>
                &bull;
                <?php echo e($publishedDate); ?>
            </div>

            <img
                class="article-image"
                src="<?php echo e($imagePath); ?>"
                alt="<?php echo e($article["title"]); ?>"
                onerror="this.onerror=null;this.src='images/default_news.jpg';"
            >

            <?php if (!empty($article["summary"])): ?>
                <div class="article-summary">
                    <?php echo e($article["summary"]); ?>
                </div>
            <?php endif; ?>

            <div class="article-content">
                <?php
                // Display article content as plain text safely.
                echo nl2br(e($article["content"]));
                ?>
            </div>

            <div class="article-footer">
                <a href="index.php" class="article-back">
                    &larr; Back to all news
                </a>
            </div>

        </article>

    </main>

</body>
</html>
