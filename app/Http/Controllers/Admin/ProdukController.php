<?php

namespace App\Http\Controllers\Admin;

use App\Models\Produk;
use App\Models\GambarProduk;
use Illuminate\Http\Request;
use App\Models\KategoriProduk;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\Datatables;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Admin\ProdukController;

class ProdukController extends Controller
{
    public function produk(){
        $kategori = KategoriProduk::where('aktif',1)->get();
        // dd($data[0]->deskripsi);
        if (request()->ajax()) {
            $data = Produk::with('gambarproduk')->where('aktif',1)->get();
            return Datatables::of($data)->make(true);
        }

        return view('admin.produk',compact('kategori'));
    }
    
    public function submit(Request $request){
        $validator = Validator::make($request->all(), [
            'gambar'     => 'required',
        ]);
        if($validator->fails()){
            $messages = $validator->messages();
            $alertMessage = $messages->first();

            return back()->with(['error' => $alertMessage]);
        }
        try {
            DB::beginTransaction();
            $harga = preg_replace('/\D/', '', $request->harga); 
            $hargaProduk = trim($harga);
            $produk = Produk::create([
                'kategori_produk_id'=>$request->kategori_id,
                'nama'=>$request->nama,
                'deskripsi'=>$request->deskripsi,
                'harga'=>$hargaProduk,
            ]);
            $gambar = $request->file('gambar');
            foreach ($gambar as $file) {
                
                $file->storeAs('public/image', $file->hashName());

                GambarProduk::create([
                    'produk_id' => $produk->id,
                    'gambar' => $file->hashName(),
                ]);
            }
            DB::commit();
            } catch (\Throwable $th) {
            DB::rollback();
            return back()->with(['error'=> $th->getMessage()]);   
            //throw $th;
        }
        return redirect('/admin/produk')->with(['success'=>'Data Berhasil Ditambah.']);   
    }

    public function edit($id){
        $kategori = KategoriProduk::where('aktif',1)->get();
        $data = Produk::find($id);
        $gambar = GambarProduk::where('produk_id',$id)->get();
        return view('admin.edit_produk',compact('data','kategori','gambar'));
    }
    public function update(Request $request, $id){
        try {
            DB::beginTransaction();
            $produk = Produk::find($id);
            $harga = preg_replace('/\D/', '', $request->harga); 
            $hargaProduk = trim($harga);
            $produk->update([
                'kategori_produk_id'=>$request->kategori_produk_id,
                'nama'=>$request->nama,
                'deskripsi'=>$request->deskripsi,
                'harga'=>$hargaProduk
            ]);
            if ($request->hasFile('gambar')) {
            
                $gambars = $request->file('gambar');
                foreach ($gambars as $file) {
    
                    $filename = $file->hashName();
                    $file->storeAs('public/image', $filename);
            
                    $gambarProduk = new GambarProduk();
                    $gambarProduk->produk_id = $produk->id;
                    $gambarProduk->gambar = $filename;
                    $gambarProduk->save();
                }
                
            }
            DB::commit();
            } catch (\Throwable $th) {
            DB::rollback();
            return back()->with(['error'=> $th->getMessage()]);   

            //throw $th;
        }
        return redirect('/admin/produk')->with(['success'=>'Data Berhasil Diupdate.']);
    }
    
    public function delete($id){
        try {
            DB::beginTransaction();
            $data = Produk::find($id);
            $data->update([
                'aktif'=> 0
            ]);
            DB::commit();
            } catch (\Throwable $th) {
            DB::rollback();
            return back()->with(['error'=> $th->getMessage()]);   

            //throw $th;
        }
        return redirect('/admin/produk')->with(['success'=>'Data Berhasil Dihapus.']);
    }

    public function delete_gambar_produk($id){
         try {
            DB::beginTransaction();
            $data = GambarProduk::find($id);
            Storage::delete('/public/image/'.$data->gambar);
            $data->delete();
            DB::commit();
            } catch (\Throwable $th) {
             DB::rollback();
             return response()->json(['error'=> $th->getMessage()],400);
            //throw $th;
         }
        return response()->json(['success'=>'Gambar Berhasil Dihapus.']);
    }

    public function frontend_produk(){
        $kategori = KategoriProduk::with('produk')->where('aktif',1)->get();
        $produk = Produk::with('kategori_produk')->get();
        return view('Frontend.Pages.produk',compact('produk','kategori'));
    }
}