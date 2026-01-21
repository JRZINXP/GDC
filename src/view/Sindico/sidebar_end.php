<?php
$current_page = basename($_SERVER['PHP_SELF']);
$is_index = ($current_page == 'index.php');

if (!$is_index):
?>
        </main>
    </div>
</body>
</html>
<?php endif; ?>