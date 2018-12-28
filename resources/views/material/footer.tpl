    <footer class="ui-footer">
        <div class="container">
            &copy; {$config["appName"]}  <a href="/staff">STAFF</a> {if $config["enable_analytics_code"]}{include file='analytics.tpl'}{/if}
        </div>
    </footer>


    <!-- js -->
    <script src="//cdn.staticfile.org/jquery/3.3.1/jquery.min.js"></script>
    {if isset($geetest_html) && $geetest_html != null}
    <script src="//static.geetest.com/static/tools/gt.js"></script>
    {/if}
    <script src="/theme/material/js/base.min.js"></script>
    <script src="/theme/material/js/project.min.js"></script>
</body>
</html>