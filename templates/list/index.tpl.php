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
                    <?php
                        // Get month from path (journal/yyyy/mm/)
                        $parts = explode('/', $entry['path']);
                        $entryMonth = $parts[1] ?? '??';

                        // Get day from filename (e.g., 30fun-thing.md)
                        $day = substr($entry['filename'], 0, 2);
                    ?>
                    <li>
                        <a href="/parser.php?file=<?php echo urlencode($entry['path']); ?>">
                            <?php echo "$entryMonth/$day - " . htmlspecialchars($entry['title']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><em>No entries found.</em></p>
        <?php endif; ?>
    </div>
</div>
