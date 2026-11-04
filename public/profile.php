<?php
require_once "../includes/db.php";
require_once "../includes/helpers.php";

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

include "../includes/header.php";
?>

<div class="container" style="
    max-width: 900px;
    margin: 60px auto; /* Increased margin for better spacing */
    padding: 0 15px; /* Added padding for smaller screens */
">
    <h2 class="page-title" style="
        text-align: center;
        margin-bottom: 30px;
        color: #343a40; /* Darker title color */
        font-weight: 600;
        letter-spacing: 1px;
    ">My Profile</h2>

    <?php if (!empty($_GET['success'])): ?>
        <div class="alert alert-success">Profile updated successfully.</div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo e($_GET['error']); ?></div>
    <?php endif; ?>


    <form action="profile_update.php" method="POST" enctype="multipart/form-data" class="card" style="
        padding: 40px; /* Increased padding */
        border-radius: 16px; /* Smoother, larger radius */
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); /* Soft, modern shadow */
        background-color: #ffffff;
        border: 1px solid #e9ecef; /* Light border */
    ">
<?php echo csrf_field(); ?>

        <h3 style="
            color: #495057;
            border-bottom: 2px solid #007bff; /* Primary color separator */
            padding-bottom: 10px;
            margin-bottom: 25px;
            font-size: 1.5rem;
        ">Personal Information</h3>
        <div class="form-group" style="margin-bottom: 20px;">
            <label style="font-weight: 600; display: block; margin-bottom: 5px; color: #6c757d;">Full Name:</label>
            <input type="text" name="full_name" class="form-control" value="<?php echo e($user['full_name']); ?>" required style="
                border-radius: 8px;
                padding: 10px 15px;
                border: 1px solid #ced4da;
                width: 100%;
                box-sizing: border-box;
            ">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="font-weight: 600; display: block; margin-bottom: 5px; color: #6c757d;">Username:</label>
            <input type="text" name="username" class="form-control" value="<?php echo e($user['username']); ?>" required style="
                border-radius: 8px;
                padding: 10px 15px;
                border: 1px solid #ced4da;
                width: 100%;
                box-sizing: border-box;
            ">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="font-weight: 600; display: block; margin-bottom: 5px; color: #6c757d;">Email:</label>
            <input type="email" name="email" class="form-control" value="<?php echo e($user['email']); ?>" required style="
                border-radius: 8px;
                padding: 10px 15px;
                border: 1px solid #ced4da;
                width: 100%;
                box-sizing: border-box;
            ">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="font-weight: 600; display: block; margin-bottom: 5px; color: #6c757d;">Phone:</label>
            <input type="text" name="phone" class="form-control" value="<?php echo e($user['phone']); ?>" required style="
                border-radius: 8px;
                padding: 10px 15px;
                border: 1px solid #ced4da;
                width: 100%;
                box-sizing: border-box;
            ">
        </div>

        <hr style="
            border: 0;
            border-top: 1px solid #dee2e6;
            margin: 35px 0;
        ">

        <h3 style="
            color: #495057;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 25px;
            font-size: 1.5rem;
        ">Avatar</h3>
        <img src="uploads/avatars/<?php echo $user['avatar_path'] ?: 'default.png'; ?>"
             style="
                width: 100px; /* Slightly smaller for a cleaner look */
                height: 100px;
                object-fit: cover;
                border-radius: 50%; /* Circular avatar */
                border: 3px solid #007bff; /* Primary color border */
                margin-bottom: 15px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
             ">
        <input type="file" name="avatar" class="form-control" style="
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
        ">

        <h3 style="
            margin-top: 35px;
            color: #495057;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 25px;
            font-size: 1.5rem;
        ">ID Card</h3>
        <img src="<?php echo e(public_base()); ?>document.php?file=<?php echo $user['id_card_path'] ?: 'no_id.png'; ?>"
             style="
                width: 250px; /* Slightly larger image */
                border-radius: 10px;
                margin-bottom: 15px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                border: 1px solid #dee2e6;
             ">
        <input type="file" name="id_card" class="form-control" style="
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
        ">

        <hr style="
            border: 0;
            border-top: 1px solid #dee2e6;
            margin: 35px 0;
        ">

        <h3 style="
            color: #495057;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 25px;
            font-size: 1.5rem;
        ">Change Password</h3>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="font-weight: 600; display: block; margin-bottom: 5px; color: #6c757d;">Current Password:</label>
            <input type="password" name="current_password" class="form-control" style="
                border-radius: 8px;
                padding: 10px 15px;
                border: 1px solid #ced4da;
                width: 100%;
                box-sizing: border-box;
            ">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="font-weight: 600; display: block; margin-bottom: 5px; color: #6c757d;">New Password:</label>
            <input type="password" name="new_password" class="form-control" style="
                border-radius: 8px;
                padding: 10px 15px;
                border: 1px solid #ced4da;
                width: 100%;
                box-sizing: border-box;
            ">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="font-weight: 600; display: block; margin-bottom: 5px; color: #6c757d;">Confirm New Password:</label>
            <input type="password" name="confirm_password" class="form-control" style="
                border-radius: 8px;
                padding: 10px 15px;
                border: 1px solid #ced4da;
                width: 100%;
                box-sizing: border-box;
            ">
        </div>

        <button class="btn btn-primary" style="
            margin-top: 30px;
            padding: 12px 25px;
            background-color: #007bff;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        " onmouseover="this.style.backgroundColor='#0056b3'" onmouseout="this.style.backgroundColor='#007bff'">Save Changes</button>
    </form>
</div>

<?php include "../includes/footer.php"; ?>