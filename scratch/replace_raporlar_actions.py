with open("htdocs/pages/raporlar.php", "r", encoding="windows-1254") as f:
    content = f.read()

target = """                                    <?php else: ?>
                                        <a href="yangin_algilama_yazdir.php?id=<?php echo $row['id']; ?>" target="_blank"
                                            class="btn btn-sm btn-outline-dark" title="Yazdır/Görüntüle">
                                            <i class="fas fa-print"></i>
                                         </a>
                                         <a href="forms/yangin_algilama_kontrol.php?id=<?php echo $row['id']; ?>"
                                             class="btn btn-sm btn-outline-primary" title="Raporu Düzenle">
                                             <i class="fas fa-edit"></i>
                                         </a>
                                         <a href="results/yangin_algilama_sonuclar.php?report_id=<?php echo $row['id']; ?>"
                                             class="btn btn-sm btn-outline-secondary" title="Sonuçları Düzenle">
                                             <i class="fas fa-list-check"></i>
                                         </a>
                                     <?php endif; ?>"""

# Since target spacing/line endings might vary, let's replace lines 301 to 314 using line list
lines = content.split("\n")

replacement = """                                    <?php elseif ($row['type'] == 'yangin'): ?>
                                        <a href="yangin_algilama_yazdir.php?id=<?php echo $row['id']; ?>" target="_blank"
                                            class="btn btn-sm btn-outline-dark" title="Yazdır/Görüntüle">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="forms/yangin_algilama_kontrol.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Raporu Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="results/yangin_algilama_sonuclar.php?report_id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary" title="Sonuçları Düzenle">
                                            <i class="fas fa-list-check"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="yangin_tesisat_yazdir.php?id=<?php echo $row['id']; ?>" target="_blank"
                                            class="btn btn-sm btn-outline-dark" title="Yazdır/Görüntüle">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="forms/yangin_tesisat_kontrol.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Raporu Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="results/yangin_tesisat_sonuclar.php?report_id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary" title="Sonuçları Düzenle">
                                            <i class="fas fa-table"></i>
                                        </a>
                                    <?php endif; ?>"""

# Replace lines 300 to 314 (0-indexed indices: 300 to 313)
# Let's inspect what lines we are replacing
print("Replaced lines:")
for idx in range(300, 314):
    print(lines[idx])

# Do the replacement
lines[300:314] = [replacement]

new_content = "\n".join(lines)

with open("htdocs/pages/raporlar.php", "w", encoding="windows-1254") as f:
    f.write(new_content)

print("Replacement done successfully!")
