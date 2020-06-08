<div class="container-fluid">
    <div class="container-fluid">
        <div class="row">
        <div class="card mb-5 col-lg-5">
            <div class="row no-gutters">
                <div class="col-md-5">
                    <img src="<?= base_url('assets/img/profile/') . $user['image']; ?>" class="card-img">
                </div>
                <div class="col-md-5">
                    <div class="card-body">
                        <h5 class="card-title"><?= $user['name']; ?></h5>
                        <p class="card-text"><?= $user['email']; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
<h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
<div class="row">
    <div class="col-lg-7">
        <h4>Contoh KHS</h4>
        <table class="table table-hover">
            <thead style="text-align: center;">
                <tr>
                    <th scope="col">No</th>
                    <th scope="col">Kode Mata Kuliah</th>
                    <th scope="col">Nama Mata Kuliah</th>
                    <th scope="col">Satuan Kredit Smester</th>
                    <th scope="col">Nilai</th>
                </tr>
            </thead>
            <tbody style="text-align: center;">
                <tr>
                    <td>1</td>
                    <td>INF61523</td>
                    <td>Algoritma Desain</td>
                    <td>3</td>
                    <td>A</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>INF61524</td>
                    <td>Pemrograman Web</td>
                    <td>3</td>
                    <td>A</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>INF61525</td>
                    <td>Informasi Manajemen</td>
                    <td>3</td>
                    <td>A</td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>INF61526</td>
                    <td>Perangkat Lunak</td>
                    <td>3</td>
                    <td>A</td>
                </tr>
                <tr>
                    <td>5</td>
                    <td>INF61527</td>
                    <td>Automata</td>
                    <td>3</td>
                    <td>B</td>
                </tr>
                <tr>
                    <td>6</td>
                    <td>INF61528</td>
                    <td>Pemrograman Objek</td>
                    <td>3</td>
                    <td>B</td>
                </tr>
            </tbody>
        </table>

    </div>
</div>
</div>