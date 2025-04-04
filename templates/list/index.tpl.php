<div class="PageWrapper">
    <div class="PagePanel">
        <div class="head"><h5 class="iUser">Journal Entries</h5></div>

        <?php if ($year): ?>
            <p>Showing entries for <strong><?php echo $year; ?></strong>
                <?php if ($month): ?>
                    /<strong><?php echo $month; ?></strong>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <p><em>No year specified.</em></p>
        <?php endif; ?>

        <?php if (!empty($entries)): ?>
            <ul>
                <?php foreach ($entries as $entry): ?>
                    <li>
                        <?php echo $entry['day'] . " " . $entry['monthWord'] . " - "; ?>
                        <a href="/parser.php?file=<?php echo urlencode($entry['path']); ?>">
                            <?php echo htmlspecialchars($entry['title']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><em>No entries found.</em></p>
        <?php endif; ?>
    </div>
</div>
