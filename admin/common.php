<?php
// admin/common.php
require_once __DIR__ . '/../init.php';
require_admin(); // проверяет роль admin или заблокирует
$csrf = csrf_token();

// небольшой layout header
function admin_header($title = 'Admin')
{
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo "<title>Admin — " . htmlspecialchars($title) . "</title>";
    echo '<style>
        body{font-family:Arial;background:#f3f6fb;margin:0;padding:16px}
        .wrap{max-width:1200px;margin:0 auto;background:#fff;padding:16px;border-radius:10px}
        .nav{display:flex;gap:8px;margin-bottom:12px}
        .box{padding:12px;border:1px solid #e6eef6;border-radius:8px;margin-bottom:12px}
        table{width:100%;border-collapse:collapse}
        th,td{padding:8px;border-bottom:1px solid #f1f5f9;text-align:left}
        .btn{padding:6px 10px;border-radius:6px;border:1px solid #e6eef6;background:#fff;cursor:pointer}
        .danger{background:#ef4444;color:#fff;border:none}
        .primary{background:#4f46e5;color:#fff;border:none}
          .footer {
      margin-top: 30px;
      padding-top: 14px;
      border-top: 1px solid #e5e7eb;
      text-align: center;
      font-size: 14px;
      color: #475569;
  }

  .footer a {
      color: #4f46e5;
      text-decoration: none;
      margin-left: 8px;
      transition: color 0.2s;
  }

  .footer a:hover {
      color: #3d35c7;
      text-decoration: underline;
  }

  .footer .dot {
      margin: 0 6px;
      color: #9ca3af;
  }
    </style></head><body><div class="wrap">';
    echo '<div class="nav"><a class="btn" href="index.php">Админ-Панель</a><a class="btn" href="users.php">Пользователи</a><a class="btn" href="activity.php">Журнал активности</a><a class="btn" href="moderation.php">Модерация отзывов</a><a class="btn" href="../index.php">Вернуться на сайт</a></div>';
    echo "<h1>$title</h1>";
}
function admin_footer()
{
    echo '</div></body></html>';
}
