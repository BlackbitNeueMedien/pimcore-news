<div class="<?= $this->mainClasses; ?>">

    <?php if ($this->paginator->getTotalItemCount() > 0) { ?>

        <div class="news-latest-content">
            <?= $this->template('news/partial/entries.php'); ?>
        </div>

    <?php } else { ?>

        <?= $this->translate('No News found') ?>

    <?php } ?>
</div>