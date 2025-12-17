<?php
$this->load->helper('demo');
$this->load->helper('assets');
?>
<!doctype html>
<html lang="en">
<head>
    <title>MySynctax Login</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato:300,400,700,900&display=swap">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://preview.colorlib.com/theme/bootstrap/login-form-17/css/style.css">
</head>
<body>
<section class="ftco-section">
    <div class="container">
        <div class="row justify-content-center">
            
        </div>
        <div class="row justify-content-center">
            <div class="col-md-12 col-lg-10">
                <div class="wrap d-md-flex">
                    <div class="text-wrap p-4 p-lg-5 text-center d-flex align-items-center order-md-last">
                        <div class="text w-100">
                       
                       <center> <?php echo img(
                        array(
                            'src' => $this->Appconfig->get_logo_image(),
									 'style' => 'width: auto;max-width: 180px',
                            )); ?>
                        </center>
                            <h2>Welcome to <?php echo $this->config->item('branding')['name']; ?></h2>
                            <p>Don't have an account?</p>
                            <a href="<?php echo base_url('')?>/developer/register" class="btn btn-white btn-outline-white">Sign Up</a>
                        </div>
                    </div>
                    <div class="login-wrap p-4 p-lg-5">
                        <?php if (validation_errors()) { ?>
                            <div class="alert alert-danger">
                                <strong><?php echo lang('common_error'); ?></strong>
                                <?php echo validation_errors(); ?>
                            </div>
                        <?php } ?>

                        <?php echo form_open('login?continue=' . rawurlencode($this->input->get('continue') ? $this->input->get('continue') : ''), ['class' => 'signin-form', 'id' => 'loginform', 'autocomplete' => 'off']); ?>

                        <div class="form-group mb-3">
                            <label class="label" for="username">Username</label>
                            <?php echo form_input([ 'name' => 'username', 'id' => 'username', 'value' => $username, 'class' => 'form-control', 'placeholder' => lang('login_username'), 'required' => true ]); ?>
                        </div>

                        <div class="form-group mb-3">
                            <label class="label" for="password">Password</label>
                            <?php echo form_password([ 'name' => 'password', 'id' => 'password', 'value' => $password, 'class' => 'form-control', 'placeholder' => lang('login_password'), 'required' => true ]); ?>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="form-control  btn-primary">Sign In</button>
                        </div>

                        <div class="form-group d-md-flex">
                            <div class="w-50 text-left">
                                <label class="checkbox-wrap checkbox-primary mb-0">Remember Me
                                    <input type="checkbox" name="remember" checked>
                                    <span class="checkmark"></span>
                                </label>
                            </div>
                            <div class="w-50 text-md-right">
                                <a href="<?php echo site_url('login/reset_password'); ?>">Forgot Password?</a>
                            </div>
                        </div>

                        <?php echo form_close(); ?>

                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<script src="<?php echo base_url('')?>template/popper.js"></script>

<script src="<?php echo base_url('')?>template/main.js"></script>

<?php if ($this->input->get('demologin')) { ?>
<script>
    $(document).ready(function() {
        $('#loginform').submit();
    });
</script>
<?php } ?>

</body>
</html>
<style>
.text-wrap {
    background: linear-gradient(to right, #62cff4, #2c67f2);
}

.btn-primary {
    background: linear-gradient(to right, #62cff4, #2c67f2);
    border: none;
    color: white;
}
</style>