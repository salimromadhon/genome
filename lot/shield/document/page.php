<?php Shield::get('header'); ?>
<main>
  <article id="page-<?php echo $page->id; ?>">
    <header>
      <h2><span><?php echo $page->title; ?></span></h2>
      <?php $update = new Date($page->update); ?>
      <p><time datetime="<?php echo $update->W3C; ?>"><strong><?php echo $language->update; ?>:</strong> <?php echo $update->F2; ?></time></p>
    </header>
    <section>
      <blockquote><?php echo $page->description; ?></blockquote>
      <?php echo $page->content; ?>
    </section>
    <footer>
      <p><strong>Author:</strong> <?php echo $page->author; ?></p>
    </footer>
  </article>
</main>
<?php Shield::get('footer'); ?>