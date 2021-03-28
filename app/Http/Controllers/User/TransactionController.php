<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Transaction,kamar,payment};
use Auth;
use Str;
use Session;

class TransactionController extends Controller
{
    // Transaction Sewa Kamar
    public function store(Request $request, $id)
    {

      if (Auth::check()) {
        if (Auth::user()->role == 'Pencari') {
          $room = kamar::where('id', $id)->first(); // Get Room by id

          $iduser = Auth::id(); // Get ID User
          $number = mt_rand(100, 999); // Get Random Number
          $date = date('dmy'); // Get Date Now
          $key = Str::random(9999);

          $kamar = new Transaction;
          $kamar->key                 = 'confirm-payment-' .$key;
          $kamar->transaction_number  = 'BOOK-' .$number .$id .'-' .$date;
          $kamar->kamar_id            = $id;
          $kamar->user_id             = Auth::id();
          $kamar->lama_sewa           = $request->lama_sewa;
          $kamar->harga_kamar         = $room->harga_kamar;
          $kamar->harga_total         = $room->harga_kamar * $request->lama_sewa + $number;
          $kamar->tgl_sewa            = $request->tgl_sewa;
          $kamar->save();

          // jika sukses Simpan ke table payment
          if ($kamar) {
            $payment = new payment;
            $payment->transaction_id    = $kamar->id;
            $payment->user_id           = $kamar->user_id;
            $payment->kamar_id          = $kamar->kamar_id;
            $payment->save();
          }

          Session::flash('success','Berhasil, Silahkan Melakukan Pembayaran');
          return redirect('/home');
        } else {
          abort(403);
        }
      }

    }

    // Detail Pembayaran
    public function detail_payment($key)
    {
      if (Auth::user()->transaksi->key == $key && Auth::user()->payment->status == 'Pending') {
        $transaksi = Transaction::where('key',$key)->first();
        return view('user.payment.show', compact('transaksi'));
      } else {
        Session::flash('error','Data Tidak Ditemukan !');
        return redirect('/home');
      }
    }

    // Transaction pembayaran room
    public function update(Request $request, $id)
    {
      $konfirmasi = Transaction::findOrFail($id);
      $konfirmasi->status     = 'Proses';
      $konfirmasi->save();

      if ($konfirmasi) {
        $payment = payment::where('transaction_id',$id)->first();
        $payment->type_transfer     = 'BANK';
        $payment->nama_bank         = $request->nama_bank;
        $payment->nama_pemilik      = $request->nama_pemilik;
        $payment->nomor_rekening    = $request->nomor_rekening;
        $payment->bank_tujuan       = $request->bank_tujuan;
        $payment->status            = 'Success';
        $payment->jumlah_bayar      = $konfirmasi->harga_total;
        $payment->tgl_transfer      = $request->tgl_transfer;
        $payment->save();
      }

      Session::flash('success','Pembayaran Terkirim');
      return redirect('/home');

      // if ($konfirmasi && $payment) {
      //   $kamar = kamar::where('id', $payment->kamar_id)->first();
      //   $kamar->sisa_kamar  = $kamar->sisa_kamar - 1;
      //   $kamar->save();
      // }
    }
}
