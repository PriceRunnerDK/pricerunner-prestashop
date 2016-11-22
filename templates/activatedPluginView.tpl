
<form class="defaultForm form-horizontal" id="module_form" method="post" action="">
    <fieldset>
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-thumbs-up"></i> Your plugin is activated!
            </div>

            <div class="form-wrapper">

                <div class="form-group">
                    <label class="control-label col-lg-3">Your website domain</label>
                    <div class="col-lg-9">
                        <input type="text" value="{{domain}}" disabled>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">Your unique Pricerunner feed url</label>
                    <div class="col-lg-9">
                        <input type="text" value="{{feedUrl}}" disabled>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">Name</label>
                    <div class="col-lg-9">
                        <input type="text" value="{{name}}" disabled>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">Phone</label>
                    <div class="col-lg-9">
                        <input type="text" value="{{phone}}" disabled>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">E-mail</label>
                    <div class="col-lg-9">
                        <input type="text" value="{{email}}" disabled>
                    </div>
                </div>
            </div>

            <div class="panel-footer">
                <div class="col-lg-9 col-lg-push-3">
                    <div class="pull-left">
                        <a href="{{feedUrl}}&amp;test=1" target="_blank" class="button btn btn-default btn-lg" onclick="return confirm('This operation might take a while and will popup in another window, do you want to continue?')">
                            Run a feed test <i class="icon-bug"></i>
                        </a>
                    </div>
                    <div class="pull-right">
                        <div class="hide" style="margin-bottom: 10px;"></div>
                        <button type="submit" name="btnResetFeed" class="button btn btn-danger btn-lg">Reset Feed</button>
                    </div>
                </div>
            </div> 
        </div>
    </fieldset>
</form>

<style type="text/css">

    .form-group { margin-bottom: 10px; }
    .button { font-size: 13px; cursor: pointer; }

</style>
