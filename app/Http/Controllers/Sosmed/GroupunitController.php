<?php

namespace App\Http\Controllers\Sosmed;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use \App\Models\Sosmed\Groupunit;

class GroupunitController extends Controller
{
    public function index(){
        if(!auth()->user()->can('Read Group')){
            return abort('403');
        }

        \DB::statement(\DB::raw('set @rownum=0'));

        $group=Groupunit::select('id','group_name','insert_user',
            'created_at','updated_at',
            \DB::raw('@rownum := @rownum + 1 AS no'));

        return \Datatables::of($group)
            ->addColumn('action',function($query){
                $html="<div class='btn-group'>";
                if(auth()->user()->can('Summary Group')){
                    $html.="<a href='".\URL::to('sosmed/group/'.$query->id.'/summary')."' class='btn btn-sm btn-success' title='Summary'><i class='icon-stats-dots'></i></a>";
                }

                if(auth()->user()->can('Edit Group')){
                    $html.="<a href='#' class='btn btn-sm btn-warning edit' kode='".$query->id."' title='Edit'><i class='fa fa-edit'></i></a>";
                }
                
                if(auth()->user()->can('Delete Group')){
                    $html.="<a href='#' class='btn btn-sm btn-danger hapus' kode='".$query->id."' title='Hapus'><i class='fa fa-trash'></i></a>";
                }
                
                $html.="</div>";

                return $html;
            })
            ->make(true);
    }

    public function store(Request $request){
        $rules=[
            'name'=>'required|max:30|regex:/^[a-zA-Z0-9_\- ]*$/'
        ];

        $validasi=\Validator::make($request->all(),$rules);

        if($validasi->fails()){
            $data=array(
                'success'=>false,
                'pesan'=>'Validasi Error',
                'error'=>$validasi->errors()->all()
            );
        }else{
            $group=new Groupunit;
            $group->group_name=$request->input('name');

            if($request->hasFile('file')){
                if (!is_dir('uploads/logo/group/')) {
                    mkdir('uploads/logo/group/', 0777, TRUE);
                }

                $file=$request->file('file');
                $filename=str_random(5).'-'.$file->getClientOriginalName();
                $destinationPath='uploads/logo/group/';
                $file->move($destinationPath,$filename);

                $group->logo=$filename;
            }

            $simpan=$group->save();

            if($simpan){
                $data=array(
                    'success'=>true,
                    'pesan'=>'Data berhasil disimpan',
                    'error'=>''
                );
            }else{
                $data=array(
                    'success'=>false,
                    'pesan'=>'Data gagal disimpan',
                    'error'=>''
                );
            }
        }

        return $data;
    }

    public function edit($id){
        $group=Groupunit::find($id);

        return $group;
    }

    public function show($id){
        $group=Groupunit::findOrFail($id);

        return $group;
    }

    public function update(Request $request,$id){
        $rules=[
            'name'=>'required|max:30|regex:/^[a-zA-Z0-9_\- ]*$/'
        ];

        $validasi=\Validator::make($request->all(),$rules);

        if($validasi->fails()){
            $data=array(
                'success'=>false,
                'pesan'=>'Validasi Error',
                'error'=>$validasi->errors()->all()
            );
        }else{
            $group=Groupunit::find($id);
            $group->group_name=$request->input('name');

            if($request->hasFile('file')){
                if (!is_dir('uploads/logo/group/')) {
                    mkdir('uploads/logo/group/', 0777, TRUE);
                }

                $file=$request->file('file');
                $filename=str_random(5).'-'.$file->getClientOriginalName();
                $destinationPath='uploads/logo/group/';
                $file->move($destinationPath,$filename);
                
                $group->logo=$filename;
            }

            $simpan=$group->save();

            if($simpan){
                $data=array(
                    'success'=>true,
                    'pesan'=>'Data berhasil disimpan',
                    'error'=>''
                );
            }else{
                $data=array(
                    'success'=>false,
                    'pesan'=>'Data gagal disimpan',
                    'error'=>''
                );
            }
        }

        return $data;
    }

    public function destroy($id){
        $group=Groupunit::find($id);

        $hapus=$group->delete();

        if($hapus){
            $data=array(
                'success'=>true,
                'pesan'=>'Data berhasil dihapus',
                'error'=>''
            );
        }else{
            $data=array(
                'success'=>false,
                'pesan'=>'Data gagal dihapus',
                'error'=>''
            );
        }

        return $data;
    }

    public function list_group(Request $request){
        $group=array();
        $sosmed=array();
        $unit=array();

        if($request->has('group')){
            $group=Groupunit::select('id','group_name')->get();
        }
        
        if($request->has('sosmed')){
            $sosmed=\App\Models\Sosmed\Sosmed::select('id','sosmed_name')->get();
        }

        if($request->has('unit')){
            $unit=\App\Models\Sosmed\Businessunit::select('id','unit_name')->get();
        }

        return array(
            'group'=>$group,
            'sosmed'=>$sosmed,
            'unit'=>$unit
        );
    }

    public function import(Request $request){
        $var = \App\Models\Sosmed\Groupunit::select('id','group_name')->get();

        return \Excel::create('group',function($excel) use($var){
            $excel->sheet('sheet1',function($sheet) use($var){
                $sheet->fromArray($var);
            });
        })->export('xlsx');
    }

    public function list_official_program_by_group(Request $request,$id){
        $sekarang=date('Y-m-d');

        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));
        }else{
            $sekarang=date('Y-m-d');
        }

        if($request->has('typeunit')){
            $typeunit=$request->input('typeunit');
        }else{
            $typeunit="TV";
        }

        $filter=$request->input('filter');

        switch($filter){
            default:
            case "all":
                $unit=\DB::select("select total.id,total.unit_name,
                    sum(total.twitter) as total_twitter,
                    sum(total.facebook) as total_facebook,
                    sum(total.instagram) as total_instagram,
                    sum(total.youtube) as total_youtube,
                    ( sum(total.twitter) + sum(total.facebook) + sum(total.instagram) + sum(total.youtube) ) as total_all
                    from 
                    (
                        select a.id,a.group_unit_id, a.unit_name, c.tanggal, 
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) twitter,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) facebook,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) instagram,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=4,c.follower,0)) youtube
                        from business_unit a
                        left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='corporate'
                        left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal='$sekarang'
                        where a.group_unit_id=$id
                        and a.type_unit!='KOL'
                        group by a.id
                        UNION ALL 
                        select if(a.id is null, 'tidak', a.business_unit_id) as idnya,d.group_unit_id, d.unit_name, c.tanggal, 
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) twitter,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) facebook,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) instagram,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=4,c.follower,0)) youtube
                        from program_unit a
                        left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
                        left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal='$sekarang'
                        left join business_unit d on d.id=a.business_unit_id and d.type_unit!='KOL'
                        where d.group_unit_id=$id
                        group by a.business_unit_id
                    ) as total
                    group by total.id
                    with ROLLUP");

                    // usort($unit, function($a, $b) {
                    //     return $b->id <=> $a->id;
                    // });

                    usort($unit, function($a, $b) {
                        return $a->total_all <=> $b->total_all;
                    });
                break;
            case 'official':
                    $unit=\DB::select("select a.id,a.group_unit_id, a.unit_name, c.tanggal, 
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) total_twitter,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) total_facebook,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) total_instagram,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=4,c.follower,0)) total_youtube,
                        ( sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) +
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) +
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) +
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=4,c.follower,0)) ) as total_all
                        from business_unit a
                        left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='corporate'
                        left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal='$sekarang'
                        where a.group_unit_id=$id
                        and a.type_unit!='KOL'
                        group by a.id
                        with ROLLUP");

                    // usort($unit, function($a, $b) {
                    //     return $b->id <=> $a->id;
                    // });

                    usort($unit, function($a, $b) {
                        return $a->total_all <=> $b->total_all;
                    });
                break;
            case 'program':
                    $unit=\DB::select("select if(a.id is null, 'tidak', a.business_unit_id) as idnya,a.business_unit_id,d.group_unit_id, d.unit_name, c.tanggal, 
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) total_twitter,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) total_facebook,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) total_instagram,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=4,c.follower,0)) total_youtube,
                        (
                            sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) +
                            sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) +
                            sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) +
                            sum(if(c.tanggal='$sekarang' and b.sosmed_id=4,c.follower,0))
                        ) as total_all
                        from program_unit a
                        left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
                        left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal='$sekarang'
                        left join business_unit d on d.id=a.business_unit_id and d.type_unit!='KOL'
                        where d.group_unit_id=$id
                        group by a.business_unit_id
                        WITH ROLLUP");

                    // usort($unit, function($a, $b) {
                    //     return $b->business_unit_id <=> $a->business_unit_id;
                    // });

                    usort($unit, function($a, $b) {
                        return $a->total_all <=> $b->total_all;
                    });
                break;
            case 'artist':
                $unit=\DB::select("select total.id,total.unit_name,
                    sum(total.twitter) as total_twitter,
                    sum(total.facebook) as total_facebook,
                    sum(total.instagram) as total_instagram,
                    sum(total.youtube) as total_youtube,
                    (
                        sum(total.twitter) +
                        sum(total.facebook) +
                        sum(total.instagram) +
                        sum(total.youtube)
                    ) as total_all
                    from 
                    (
                        select a.id,a.group_unit_id, a.unit_name, c.tanggal, 
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) twitter,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) facebook,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) instagram,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=4,c.follower,0)) youtube
                        from business_unit a
                        left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='corporate'
                        left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal='$sekarang'
                        where a.group_unit_id=$id
                        and a.type_unit='KOL'
                        group by a.id
                        UNION ALL 
                        select if(a.id is null, 'tidak', a.business_unit_id) as idnya,d.group_unit_id, d.unit_name, c.tanggal, 
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) twitter,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) facebook,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) instagram,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=4,c.follower,0)) youtube
                        from program_unit a
                        left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
                        left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal='$sekarang'
                        left join business_unit d on d.id=a.business_unit_id and d.type_unit='KOL'
                        where d.group_unit_id=$id
                        group by a.business_unit_id
                    ) as total
                    group by total.id
                    WITH ROLLUP");

                    // usort($unit, function($a, $b) {
                    //     return $b->id <=> $a->id;
                    // });

                    usort($unit, function($a, $b) {
                        return $a->total_all <=> $b->total_all;
                    });
                break;
        }

        $tambahanInews=\DB::select("select ifnull(a.id,'TOTAL') as idnya, a.business_unit_id,
                d.group_unit_id, a.program_name,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) as total_twitter,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) as total_facebook,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) as total_instagram,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=4, c.follower,0)) as total_youtube
                from program_unit a 
                left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
                left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal='$sekarang'
                left join business_unit d on d.id=a.business_unit_id and d.type_unit='TV'
                where a.id in (89, 101, 95, 87)
                group by a.id
                with ROLLUP
                HAVING idnya='TOTAL'");

        return array('chart'=>$unit,'inews'=>$tambahanInews);
    }
}