<?php

namespace App\Services\Owner;
use ErrorException;

use App\Models\{DataUser,kamar,fkamar,fkamar_mandi,fbersama,fparkir,area,fotokamar,provinsi};
use Auth;
use Session;
use file;

class KamarService {

  // Index
  public function index()
  {
    try {
      $kamar = kamar::where('user_id',auth::user()->id)->get();
      return view('pemilik.kamar.index', compact('kamar'));
    } catch (ErrorException $e) {
      throw new ErrorException($e->getMessage());
    }
  }


  // Create Form
  public function create()
  {
    try {
      $provinsi = provinsi::select('kode','nama')->get();
      // Cek data bank
      if ($this->databank()) {
        Session::flash('error','Data Akun Belum Lengkap !');
        return redirect('/home');
      }
      return view('pemilik.kamar.create', compact('provinsi'));
    } catch (ErrorException $e) {
      throw new ErrorException($e->getMessage());
    }
  }


  // Store
  public function store($params)
  {
    try {
      $foto = $params->file('bg_foto');
      $nama_foto = time()."_".$foto->getClientOriginalName();
      // isi dengan nama folder tempat kemana file diupload
      $tujuan_upload = 'images/bg_foto';
      $foto->move($tujuan_upload,$nama_foto);

      $slug = \Str::slug($params->nama_kamar) . "-" . \Str::random(6);
      $kamar = new Kamar;
      $kamar->id = $params->id;
      $kamar->user_id = auth::id();
      $kamar->slug = $slug;
      $kamar->nama_kamar = $params->nama_kamar;
      $kamar->jenis_kamar = $params->jenis_kamar;
      $kamar->luas_kamar = $params->luas_kamar;
      $kamar->stok_kamar = $params->stok_kamar;
      $kamar->sisa_kamar = $kamar->stok_kamar;
      $kamar->harga_kamar = $params->harga_kamar;
      $kamar->ket_lain = $params->ket_lain;
      $kamar->ket_biaya = $params->ket_biaya;
      $kamar->desc = $params->desc;
      $kamar->kategori = $params->kategori;
      $kamar->book = $params->book;
      $kamar->bg_foto = $nama_foto;
      $kamar->provinsi_id = $params->provinsi_id;
      $kamar->save();

      if ($kamar) {
          foreach($params->addmore as $value){
            $fkamar = new fkamar;
            $fkamar->kamar_id = $kamar->id;
            $fkamar->name = $value['name'];
            $fkamar->save();
          }
      }

      if ($kamar && $fkamar) {
          foreach ($params->addkm as $value) {
            $fkamar_mandi = new fkamar_mandi;
            $fkamar_mandi->kamar_id = $kamar->id;
            $fkamar_mandi->name = $value['name'];
            $fkamar_mandi->save();
          }
      }

      if ($kamar && $fkamar && $fkamar_mandi) {
          foreach ($params->addbersama as $value) {
            $fbersama = new fbersama;
            $fbersama->kamar_id = $kamar->id;
            $fbersama->name = $value['name'];
            $fbersama->save();
          }
      }

      if ($kamar && $fkamar && $fkamar_mandi && $fbersama) {
          foreach ($params->addparkir as $value) {
            $fparkir = new fparkir;
            $fparkir->kamar_id = $kamar->id;
            $fparkir->name = $value['name'];
            $fparkir->save();
          }
      }

      if ($kamar && $fkamar && $fkamar_mandi && $fbersama && $fparkir) {
          foreach ($params->addarea as $value) {
            $area = new area;
            $area->kamar_id =  $kamar->id;
            $area->name = $value['name'];
            $area->save();
          }
      }

      if ($kamar&& $fkamar&& $fkamar_mandi&& $fbersama&& $fparkir&& $area) {
          foreach($params->addfoto as $value) {
            $foto_kamar = $value['foto_kamar'];
            $nama_foto = time()."_".$foto_kamar->getClientOriginalName();
            // isi dengan nama folder tempat kemana file diupload
            $tujuan_upload = 'images/foto_kamar';
            $foto_kamar->move($tujuan_upload,$nama_foto);

            $foto = new fotokamar;
            $foto->kamar_id = $kamar->id;
            $foto->foto_kamar = $nama_foto;
            $foto->save();
          }
      }

      Session::flash('success','Kamar berhasil ditambah');
      return redirect('pemilik/kamar');
    } catch (ErrorException $e) {
      throw new ErrorException($e->getMessage());
    }
  }


  // Cek data bank user
    protected function databank()
    {
      $databank = Auth::user()->datauser->nama_bank == NULL && Auth::user()->datauser->nama_pemilik == NULL && Auth::user()->datauser->nomor_rekening == NULL;

      return $databank;
    }
}