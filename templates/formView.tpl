<form class="defaultForm form-horizontal" id="module_form" method="post" action="">
    <fieldset>
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-user"></i> Application form
            </div>

            <div class="form-wrapper">

                <div class="form-group">
                    <label class="control-label col-lg-3">Domain</label>
                    <div class="col-lg-9">
                        <input type="text" value="{{domain}}" disabled>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">Feed url</label>
                    <div class="col-lg-9">
                        <input type="text" value="{{feedUrl}}" disabled>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">Name</label>
                    <div class="col-lg-9">
                        <input class="" id="PRICERUNNER_NAME" name="PRICERUNNER_NAME" type="text" value="{{name}}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">Phone</label>
                    <div class="col-lg-9">
                        <input class="" id="PRICERUNNER_PHONE" name="PRICERUNNER_PHONE" type="text" value="">
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">E-mail</label>
                    <div class="col-lg-9">
                        <input class="" id="PRICERUNNER_MAIL" name="PRICERUNNER_MAIL" type="text" value="{{email}}">
                    </div>
                </div>
            </div>

            <div class="panel-footer">
                <button type="submit" value="1" id="module_form_submit_btn" name="btnSubmit" class="btn btn-default button pull-right">
                    <i class="process-icon-save"></i> Activate
                </button>
            </div> 
        </div>
    </fieldset>
</form>

<style type="text/css">

    .form-group { margin-bottom: 10px; }

</style>
