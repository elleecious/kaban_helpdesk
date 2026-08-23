<?php include('includes/header.php') ?>
<?php $page_title="KabanDesk"; ?>

<div class="bg-image">
    <div class="mask"></div>
    <div class="container py-5">
        <div class="row">
            <div class="col-md-12" style="margin-top: 160px;">
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <h1 class="text-center white-text">KabanDesk</h1>
                        <h6 class="text-center white-text">Every issue tracked. Every guest experience protected.</h6>
                        <!-- <div class="row">
                            <div class="col-4">
                                <img class="mx-auto d-block" src="./assets/img/kaban_logo.png">
                            </div>
                        </div> -->
                        <div class="card rounded-0 bg-transparent z-depth-0">
                            <div class="card-body text-white">
                                <form class="form" method="post" autocomplete="on" id="formLogin">
                                    <div class="md-form">
                                        <i class="fa fa-envelope prefix"></i>
                                        <input class="form-control white-text" type="email" name="email" id="email" required>
                                        <label class="white-text" for="email">Email Address</label>
                                    </div>
                                    <div class="md-form">
                                        <i class="fa fa-lock prefix"></i>
                                        <input class="form-control white-text" type="password" name="password" id="password" required>
                                        <label class="white-text" for="password">Password</label>
                                    </div>
                                    <div class="d-flex justify-content-center">
                                        <button class="btn btn-lg btn-rounded col-md-12 text-white btn-secondary" type="submit" name="btnLogin" id="btnLogin">Login</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include('includes/footer.php') ?>