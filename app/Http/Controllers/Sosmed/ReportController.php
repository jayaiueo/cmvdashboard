<?php

namespace App\Http\Controllers\Sosmed;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;


class ReportController extends Controller
{
    public function summary_program_by_id($id){
        $program=\App\Models\Sosmed\Programunit::with(
            [
                'sosmed',
                'sosmed.sosmed',
                'sosmed.followers'
            ]
        )->find($id);

        return $program;
    }

    public function target_vs_achievement(Request $request){
        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }else{
            $sekarang=date('Y-m-d');
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }

        if($request->has('group')){
            $group=$request->input('group');
        }else{
            $group=1;
        }

        $target=\DB::select("select a.id, a.group_unit_id, a.unit_name, b.target_use, 
            sum(if(b.sosmed_id=1, c.target,0)) as target_tw,
            sum(if(d.tanggal='$sekarang' and b.sosmed_id=1, d.follower,0)) as follower_tw,
            ( sum(if(d.tanggal='$sekarang' and b.sosmed_id=1, d.follower,0)) / sum(if(b.sosmed_id=1, c.target,0)) * 100) as acv_tw,
            sum(if(b.sosmed_id=2, c.target,0)) as target_fb,
            sum(if(d.tanggal='$sekarang' and b.sosmed_id=2, d.follower,0)) as follower_fb,
            ( sum(if(d.tanggal='$sekarang' and b.sosmed_id=2, d.follower,0)) / sum(if(b.sosmed_id=2, c.target,0)) * 100) as acv_fb,
            sum(if(b.sosmed_id=3, c.target,0)) as target_ig,
            sum(if(d.tanggal='$sekarang' and b.sosmed_id=3, d.follower,0)) as follower_ig,
            ( sum(if(d.tanggal='$sekarang' and b.sosmed_id=3, d.follower,0)) / sum(if(b.sosmed_id=3, c.target,0)) * 100) as acv_ig
            from business_unit a
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='corporate'
            left join unit_sosmed_target c on c.unit_sosmed_id=b.id
            left join unit_sosmed_follower d on d.unit_sosmed_id=b.id and d.tanggal='$sekarang'
            where a.group_unit_id='$group'
            GROUP by a.id");

        $tambahanInews=\DB::select("select ifnull(a.id,'TOTAL') as id, a.business_unit_id,
            d.group_unit_id, a.program_name,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) as tw_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) as tw_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) -1)*100 as growth_tw,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) as fb_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) as fb_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) -1)*100 as growth_fb,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) as ig_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) as ig_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) -1)*100 as growth_ig
            from program_unit a 
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
            left join business_unit d on d.id=a.business_unit_id
            where a.id in (89, 101, 95, 87)
            group by a.id
            with ROLLUP");

        return view('sosmed.view.target_vs_achivement')
            ->with('targetVsAch',$target)
            ->with('tambahanInews',$tambahanInews)
            ->with('sekarang',$sekarang);
    }

    public function official_account_all_tv(Request $request){
        $allsosmed=\App\Models\Sosmed\Sosmed::select('id','sosmed_name')->get();

        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }else{
            $sekarang=date('Y-m-d');
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }

        if($request->has('group')){
            $group="where group_unit_id='".$request->input('group')."'";
        }

        $unit=\DB::select("select ifnull(a.id,'SUBTOTAL') as id, a.unit_name,  
            ifnull(a.group_unit_id,'TOTAL') as group_id,e.group_name,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=1,c.follower,0)) as tw_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) as tw_sekarang,
            ((sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=1,c.follower,0)) -1) * 100) as growth_tw,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=2,c.follower,0)) as fb_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) as fb_sekarang,
            ((sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=2,c.follower,0)) - 1) * 100) as growth_fb,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=3,c.follower,0)) as ig_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) as ig_sekarang,
            ((sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=3,c.follower,0)) - 1) * 100) as growth_ig
            from 
            business_unit a 
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='corporate'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
            left join group_unit e on e.id=a.group_unit_id
            group by a.group_unit_id,a.id
            with ROLLUP");

        $tambahanInews=\DB::select("select ifnull(a.id,'TOTAL') as id, a.business_unit_id,
            d.group_unit_id, a.program_name,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) as tw_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) as tw_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) -1)*100 as growth_tw,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) as fb_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) as fb_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) -1)*100 as growth_fb,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) as ig_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) as ig_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) -1)*100 as growth_ig
            from program_unit a 
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
            left join business_unit d on d.id=a.business_unit_id
            where a.id in (89, 101, 95, 87)
            group by a.id
            with ROLLUP");

        return view('sosmed.view.official_account_all_tv')
            ->with('officialTv',$unit)
            ->with('sekarang',$sekarang)
            ->with('kemarin',$kemarin)
            ->with('tambahanInews',$tambahanInews);
    }

    public function sosmed_official_and_program(Request $request){
        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }else{
            $sekarang=date('Y-m-d');
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }

        $overallOfficialTv=\DB::select("select terjadi.id, terjadi.unit_name, terjadi.group_id, terjadi.group_name,
            sum(terjadi.tw_kemarin) as total_tw_kemarin,
            sum(terjadi.tw_sekarang) as total_tw_sekarang,
            ((sum(terjadi.tw_sekarang) / sum(terjadi.tw_kemarin) - 1) * 100) as total_growth_tw,
            (sum(terjadi.tw_sekarang) - sum(terjadi.tw_kemarin)) as total_num_of_growth_tw,
            sum(terjadi.fb_kemarin) as total_fb_kemarin,
            sum(terjadi.fb_sekarang) as total_fb_sekarang,
            ((sum(terjadi.fb_sekarang) / sum(terjadi.fb_kemarin) - 1) * 100) as total_growth_fb,
            (sum(terjadi.fb_sekarang) - sum(terjadi.fb_kemarin)) as total_num_of_growth_fb,
            sum(terjadi.ig_kemarin) as total_ig_kemarin,
            sum(terjadi.ig_sekarang) as total_ig_sekarang,
            ((sum(terjadi.ig_sekarang) / sum(terjadi.ig_kemarin) - 1) * 100) as total_growth_ig,
            (sum(terjadi.ig_sekarang) - sum(terjadi.ig_kemarin)) as total_num_of_growth_ig 
            from
            (
                select ifnull(a.id,'SUBTOTAL') as id, a.unit_name,  
                ifnull(a.group_unit_id,'TOTAL') as group_id,e.group_name,
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=1,d.follower,0)) as tw_kemarin,
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=1,d.follower,0)) as tw_sekarang,
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=2,d.follower,0)) as fb_kemarin,
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=2,d.follower,0)) as fb_sekarang,
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=3,d.follower,0)) as ig_kemarin,
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=3,d.follower,0)) as ig_sekarang
                from 
                business_unit a 
                left join program_unit b on b.business_unit_id=a.id
                left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='program'
                left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal BETWEEN '$kemarin' and '$sekarang'
                left join group_unit as e on e.id=a.group_unit_id
                group by a.group_unit_id,a.id
                with ROLLUP
                union all 
                select ifnull(a.id,'SUBTOTAL') as id, a.unit_name, 
                ifnull(a.group_unit_id,'TOTAL') as group_id,e.group_name,
                sum(if(c.tanggal='$kemarin' and b.sosmed_id=1,c.follower,0)) as tw_kemarin,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) as tw_sekarang,
                sum(if(c.tanggal='$kemarin' and b.sosmed_id=2,c.follower,0)) as fb_kemarin,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) as fb_sekarang,
                sum(if(c.tanggal='$kemarin' and b.sosmed_id=3,c.follower,0)) as ig_kemarin,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) as ig_sekarang
                from 
                business_unit a 
                left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='corporate'
                left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
                left join group_unit as e on e.id=a.group_unit_id
                group by a.group_unit_id,a.id
                with ROLLUP
            ) as terjadi
            group by terjadi.group_id,terjadi.id");

        return view('sosmed.view.sosmed_official_and_program')
                ->with('overallOfficialTv',$overallOfficialTv)
                ->with('sekarang',$sekarang)
                ->with('kemarin',$kemarin);
    }

    public function official_and_program(Request $request){
        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));
        }else{
            $sekarang=date('Y-m-d');
        }

        if($request->has('group')){
            $group=$request->input('group');
        }

        $officialAndProgram=\DB::select("select 'corporate' as urut,a.id, a.group_unit_id, a.unit_name, 
            b.type_sosmed, b.unit_sosmed_name, c.tanggal, 
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) as tw,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) as fb,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) as ig
            from business_unit a
            left join unit_sosmed as b on b.business_program_unit=a.id and b.type_sosmed='corporate'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal='$sekarang'
            where a.group_unit_id='$group'
            group by a.id
            union all 
            select 'program' as urut,d.id, d.group_unit_id, d.unit_name, b.type_sosmed,a.program_name,c.tanggal, 
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) as tw,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) as fb,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) as ig
            from program_unit a 
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal='$sekarang'
            left join business_unit d on d.id=a.business_unit_id
            where d.group_unit_id='$group'
            group by a.id
            union all 
            select 'total' as urut,semua.id, semua.group_unit_id, semua.unit_name, semua.type_sosmed, semua.unit_sosmed_name, semua.tanggal,
                sum(tw) as total_tw,
                sum(fb) as total_fb,
                sum(ig) as total_ig
                from (
                    select a.id,a.group_unit_id, a.unit_name, b.type_sosmed, b.unit_sosmed_name, c.tanggal, 
                    sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) as tw,
                    sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) as fb,
                    sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) as ig
                    from business_unit a
                    left join unit_sosmed as b on b.business_program_unit=a.id and b.type_sosmed='corporate'
                    left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal='$sekarang'
                    where a.group_unit_id='$group'
                    group by a.id
                    union all 
                    select d.id,d.group_unit_id, d.unit_name, b.type_sosmed,a.program_name,c.tanggal, 
                    sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) as tw,
                    sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) as fb,
                    sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) as ig
                    from program_unit a 
                    left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
                    left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal='$sekarang'
                    left join business_unit d on d.id=a.business_unit_id
                    where d.group_unit_id='$group'
                    group by a.id
                ) as semua 
            group by semua.id
            order by id, urut,type_sosmed");

        return view('sosmed.view.detail_official_and_program')
            ->with('sekarang',$sekarang)
            ->with('officialAndProgram',$officialAndProgram);
    }

    /* rank */
    public function rank_of_official_account_all_group(Request $request){
        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));

            if($request->has('pilih')){
                $pilih=$request->input('pilih');

                if($pilih=="on"){
                    if($request->has('kemarin')){
                        $kemarin=date('Y-m-d',strtotime($request->input('kemarin')));
                    }else{
                        $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
                    }
                }else{
                    $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
                }
            }else{
                $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
            }
            
        }else{
            $sekarang=date('Y-m-d');
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }

        $data['sekarang']=$sekarang;
        $data['kemarin']=$kemarin;


        $rankOfOfficialAccountAllGroupByFollowers=\DB::select("select a.id, a.group_name,d.tanggal,
            sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) as tw_kemarin,
            sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) as tw_sekarang,
            ((sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) - 1) * 100) as growth_tw,
            (sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_tw,
            sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) as fb_kemarin,
            sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) as fb_sekarang,
            ((sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) - 1) * 100) as growth_fb,
            (sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_fb,
            sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) as ig_kemarin,
            sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) as ig_sekarang,
            ((sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) - 1) * 100) as growth_ig,
            (sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_ig
            FROM group_unit a 
            left join business_unit b on b.group_unit_id=a.id
            left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='corporate'
            left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal BETWEEN '$kemarin' and '$sekarang'
            group by a.id");

        $rankOfOfficialAccountAllTvByFollowers=\DB::select("select b.id, b.unit_name,d.tanggal,
            sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) as tw_kemarin,
            sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) as tw_sekarang,
            ((sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) -1) * 100) as growth_tw,
            (sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_tw,
            sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) as fb_kemarin,
            sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) as fb_sekarang,
            ((sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) -1) * 100) as growth_fb,
            (sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_fb,
            sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) as ig_kemarin,
            sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) as ig_sekarang,
            ((sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) -1) * 100) as growth_ig,
            (sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_ig
            FROM business_unit b
            left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='corporate'
            left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal BETWEEN '$kemarin' and '$sekarang'
            group by b.id");

        $rankOverallAccountGroup=\DB::select("select terjadi.group_unit_id,terjadi.group_name,
            sum(terjadi.tw_kemarin) as total_tw_kemarin,
            sum(terjadi.tw_sekarang) as total_tw_sekarang,
            ((sum(terjadi.tw_sekarang) / sum(terjadi.tw_kemarin) - 1) * 100) as total_growth_tw,
            (sum(terjadi.tw_sekarang) - sum(terjadi.tw_kemarin)) as total_num_of_growth_tw,
            sum(terjadi.fb_kemarin) as total_fb_kemarin,
            sum(terjadi.fb_sekarang) as total_fb_sekarang,
            ((sum(terjadi.fb_sekarang) / sum(terjadi.fb_kemarin) - 1) * 100) as total_growth_fb,
            (sum(terjadi.fb_sekarang) - sum(terjadi.fb_kemarin)) as total_num_of_growth_fb,
            sum(terjadi.ig_kemarin) as total_ig_kemarin,
            sum(terjadi.ig_sekarang) as total_ig_sekarang,
            ((sum(terjadi.ig_sekarang) / sum(terjadi.ig_kemarin) - 1) * 100) as total_growth_ig,
            (sum(terjadi.ig_sekarang) - sum(terjadi.ig_kemarin)) as total_num_of_growth_ig
                from (
                    select a.id, a.unit_name,c.unit_sosmed_name ,d.tanggal,  
                    a.group_unit_id,e.group_name,
                    sum(if(d.tanggal='$kemarin' and c.sosmed_id=1,d.follower,0)) as tw_kemarin,
                    sum(if(d.tanggal='$sekarang' and c.sosmed_id=1,d.follower,0)) as tw_sekarang,
                    sum(if(d.tanggal='$kemarin' and c.sosmed_id=2,d.follower,0)) as fb_kemarin,
                    sum(if(d.tanggal='$sekarang' and c.sosmed_id=2,d.follower,0)) as fb_sekarang,
                    sum(if(d.tanggal='$kemarin' and c.sosmed_id=3,d.follower,0)) as ig_kemarin,
                    sum(if(d.tanggal='$sekarang' and c.sosmed_id=3,d.follower,0)) as ig_sekarang
                    from 
                    business_unit a 
                    left join program_unit b on b.business_unit_id=a.id
                    left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='program'
                    left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal BETWEEN '$kemarin' and '$sekarang'
                    left join group_unit e on e.id=a.group_unit_id
                    group by a.id
                    union all 
                    select a.id, a.unit_name,b.unit_sosmed_name ,c.tanggal,  
                    a.group_unit_id,e.group_name,
                    sum(if(c.tanggal='$kemarin' and b.sosmed_id=1,c.follower,0)) as tw_kemarin,
                    sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) as tw_sekarang,
                    sum(if(c.tanggal='$kemarin' and b.sosmed_id=2,c.follower,0)) as fb_kemarin,
                    sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) as fb_sekarang,
                    sum(if(c.tanggal='$kemarin' and b.sosmed_id=3,c.follower,0)) as ig_kemarin,
                    sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) as ig_sekarang
                    from 
                    business_unit a 
                    left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='corporate'
                    left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
                    left join group_unit e on e.id=a.group_unit_id
                    group by a.id 
                ) as terjadi
            group by terjadi.group_unit_id");

        $rankOverallAccountAllTv=\DB::select("select terjadi.id, terjadi.group_unit_id,terjadi.unit_name,
            sum(terjadi.tw_kemarin) as total_tw_kemarin,
            sum(terjadi.tw_sekarang) as total_tw_sekarang,
            ((sum(terjadi.tw_sekarang) / sum(terjadi.tw_kemarin) - 1) * 100) as total_growth_tw,
            (sum(terjadi.tw_sekarang) - sum(terjadi.tw_kemarin)) as total_num_of_growth_tw,
            sum(terjadi.fb_kemarin) as total_fb_kemarin,
            sum(terjadi.fb_sekarang) as total_fb_sekarang,
            ((sum(terjadi.fb_sekarang) / sum(terjadi.fb_kemarin) - 1) * 100) as total_growth_fb,
            (sum(terjadi.fb_sekarang) - sum(terjadi.fb_kemarin)) as total_num_of_growth_fb,
            sum(terjadi.ig_kemarin) as total_ig_kemarin,
            sum(terjadi.ig_sekarang) as total_ig_sekarang,
            ((sum(terjadi.ig_sekarang) / sum(terjadi.ig_kemarin) - 1) * 100) as total_growth_ig,
            (sum(terjadi.ig_sekarang) - sum(terjadi.ig_kemarin)) as total_num_of_growth_ig
            from (
                select a.id, a.unit_name,c.unit_sosmed_name ,d.tanggal,  
                a.group_unit_id,
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=1,d.follower,0)) as tw_kemarin,
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=1,d.follower,0)) as tw_sekarang,
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=2,d.follower,0)) as fb_kemarin,
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=2,d.follower,0)) as fb_sekarang,
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=3,d.follower,0)) as ig_kemarin,
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=3,d.follower,0)) as ig_sekarang
                from 
                business_unit a 
                left join program_unit b on b.business_unit_id=a.id
                left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='program'
                left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal BETWEEN '$kemarin' and '$sekarang'
                group by a.id
                union all 
                select a.id, a.unit_name,b.unit_sosmed_name ,c.tanggal,  
                a.group_unit_id,
                sum(if(c.tanggal='$kemarin' and b.sosmed_id=1,c.follower,0)) as tw_kemarin,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) as tw_sekarang,
                sum(if(c.tanggal='$kemarin' and b.sosmed_id=2,c.follower,0)) as fb_kemarin,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) as fb_sekarang,
                sum(if(c.tanggal='$kemarin' and b.sosmed_id=3,c.follower,0)) as ig_kemarin,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) as ig_sekarang
                from 
                business_unit a 
                left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='corporate'
                left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
                group by a.id 
            ) as terjadi
            group by terjadi.id");

        $groupOthers=\DB::select("select 'corporate' as urut,a.id, a.group_unit_id, a.unit_name, 
            b.type_sosmed, b.unit_sosmed_name, c.tanggal, 
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=1,c.follower,0)) as tw_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) as tw_sekarang,
            ( sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=1,c.follower,0)) - 1) * 100 as growth_tw,
            ( sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=1,c.follower,0))) as num_of_growth_tw,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=2,c.follower,0)) as fb_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) as fb_sekarang,
            ( sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=2,c.follower,0)) - 1) * 100 as growth_fb,
            ( sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=2,c.follower,0))) as num_of_growth_fb,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=3,c.follower,0)) as ig_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) as ig_sekarang,
            ( sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=3,c.follower,0)) - 1) * 100 as growth_ig,
            ( sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=3,c.follower,0))) as num_of_growth_ig
            from business_unit a
            left join unit_sosmed as b on b.business_program_unit=a.id and b.type_sosmed='corporate'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal between '$kemarin' and '$sekarang'
            where a.group_unit_id=5
            group by a.id");

        $tambahanInews=\DB::select("select ifnull(a.id,'TOTAL') as id, a.business_unit_id,
            d.group_unit_id, a.program_name,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) as tw_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) as tw_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) -1)*100 as growth_tw,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0))) as num_of_growth_tw,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) as fb_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) as fb_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) -1)*100 as growth_fb,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0))) as num_of_growth_fb,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) as ig_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) as ig_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) -1)*100 as growth_ig,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0))) as num_of_growth_ig
            from program_unit a 
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
            left join business_unit d on d.id=a.business_unit_id
            where a.id in (89, 101, 95, 87)
            group by a.id
            with ROLLUP");

        $tambahanOverAllTvOthers=\DB::select("select terjadi.id, terjadi.unit_name, terjadi.group_id, terjadi.group_name,
            sum(terjadi.tw_kemarin) as total_tw_kemarin,
            sum(terjadi.tw_sekarang) as total_tw_sekarang,
            ((sum(terjadi.tw_sekarang) / sum(terjadi.tw_kemarin) - 1) * 100) as total_growth_tw,
            (sum(terjadi.tw_sekarang) - sum(terjadi.tw_kemarin)) as total_num_of_growth_tw,
            sum(terjadi.fb_kemarin) as total_fb_kemarin,
            sum(terjadi.fb_sekarang) as total_fb_sekarang,
            ((sum(terjadi.fb_sekarang) / sum(terjadi.fb_kemarin) - 1) * 100) as total_growth_fb,
            (sum(terjadi.fb_sekarang) - sum(terjadi.fb_kemarin)) as total_num_of_growth_fb,
            sum(terjadi.ig_kemarin) as total_ig_kemarin,
            sum(terjadi.ig_sekarang) as total_ig_sekarang,
            ((sum(terjadi.ig_sekarang) / sum(terjadi.ig_kemarin) - 1) * 100) as total_growth_ig,
            (sum(terjadi.ig_sekarang) - sum(terjadi.ig_kemarin)) as total_num_of_growth_ig 
            from
            (
                select ifnull(a.id,'SUBTOTAL') as id, a.unit_name,  
                ifnull(a.group_unit_id,'TOTAL') as group_id,e.group_name,
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=1,d.follower,0)) as tw_kemarin,
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=1,d.follower,0)) as tw_sekarang,
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=2,d.follower,0)) as fb_kemarin,
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=2,d.follower,0)) as fb_sekarang,
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=3,d.follower,0)) as ig_kemarin,
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=3,d.follower,0)) as ig_sekarang
                from 
                business_unit a 
                left join program_unit b on b.business_unit_id=a.id
                left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='program'
                left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal BETWEEN '$kemarin' and '$sekarang'
                left join group_unit as e on e.id=a.group_unit_id
                where a.group_unit_id=5
                group by a.group_unit_id,a.id
                union all 
                select ifnull(a.id,'SUBTOTAL') as id, a.unit_name, 
                ifnull(a.group_unit_id,'TOTAL') as group_id,e.group_name,
                sum(if(c.tanggal='$kemarin' and b.sosmed_id=1,c.follower,0)) as tw_kemarin,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) as tw_sekarang,
                sum(if(c.tanggal='$kemarin' and b.sosmed_id=2,c.follower,0)) as fb_kemarin,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) as fb_sekarang,
                sum(if(c.tanggal='$kemarin' and b.sosmed_id=3,c.follower,0)) as ig_kemarin,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) as ig_sekarang
                from 
                business_unit a 
                left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='corporate'
                left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
                left join group_unit as e on e.id=a.group_unit_id
                where a.group_unit_id=5
                group by a.group_unit_id,a.id
            ) as terjadi
            group by terjadi.group_id,terjadi.id");


        return view('sosmed.view.rank_official_account_all_group_by_followers')
            ->with('sekarang',$sekarang)
            ->with('kemarin',$kemarin)
            ->with('rankOfOfficialAccountAllGroupByFollowers',$rankOfOfficialAccountAllGroupByFollowers)
            ->with('rankOfOfficialAccountAllTvByFollowers',$rankOfOfficialAccountAllTvByFollowers)
            ->with('rankOverallAccountGroup',$rankOverallAccountGroup)
            ->with('rankOverallAccountAllTv',$rankOverallAccountAllTv)
            ->with('tambahanOverAllTvOthers',$tambahanOverAllTvOthers)
            ->with('tambahanInews',$tambahanInews)
            ->with('groupOthers',$groupOthers);
    }

    public function rank_of_official_account_all_tv(Request $request){
        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }else{
            $sekarang=date('Y-m-d');
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }

        $group=\DB::select("select b.id, b.unit_name,d.tanggal,
            sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) as tw_kemarin,
            sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) as tw_sekarang,
            (sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) -1) as growth_tw,
            (sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_tw,
            sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) as fb_kemarin,
            sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) as fb_sekarang,
            (sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) -1) as growth_fb,
            (sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_fb,
            sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) as ig_kemarin,
            sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) as ig_sekarang,
            (sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) -1) as growth_ig,
            (sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_ig
            FROM business_unit b
            left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='corporate'
            left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal BETWEEN '$kemarin' and '$sekarang'
            group by b.id");

        $tambahanInews=\DB::select("select ifnull(a.id,'TOTAL') as id, a.business_unit_id,
            d.group_unit_id, a.program_name,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) as tw_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) as tw_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) -1)*100 as growth_tw,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0))) as num_of_growth_tw,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) as fb_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) as fb_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) -1)*100 as growth_fb,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0))) as num_of_growth_fb,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) as ig_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) as ig_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) -1)*100 as growth_ig,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0))) as num_of_growth_ig
            from program_unit a 
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
            left join business_unit d on d.id=a.business_unit_id
            where a.id in (89, 101, 95, 87)
            group by a.id
            with ROLLUP");

        $data=array();

        $arrTw=array();
        $arrFb=array();
        $arrIg=array();
        foreach($group as $k){
            if($k->id==4){
                foreach($tambahanInews as $in){
                    if($in->id=="TOTAL"){
                        array_push($arrTw,$in->tw_sekarang);
                        array_push($arrFb,$in->fb_sekarang);
                        array_push($arrIg,$in->ig_sekarang);        
                    }
                }
            }else{
                array_push($arrTw,$k->tw_sekarang);
                array_push($arrFb,$k->fb_sekarang);
                array_push($arrIg,$k->ig_sekarang);
            }
        }
        $rankTw=$arrTw;
        $rankFb=$arrFb;
        $rankIg=$arrIg;

        rsort($rankTw);
        rsort($rankFb);
        rsort($rankIg);

        $rankTw=array_flip($rankTw);
        $rankFb=array_flip($rankFb);
        $rankIg=array_flip($rankIg);

        foreach($group as $row){
            if($row->id==4){
                foreach($tambahanInews as $ins){
                    if($ins->id=="TOTAL"){
                        $data[]=array(
                            'id'=>$row->id,
                            'unit_name'=>$row->unit_name,
                            'follower'=>array(
                                'tw'=>1,
                                'growth_twitter'=>$ins->growth_tw." %",
                                'tw_sekarang'=>$ins->tw_sekarang,
                                'rank_tw'=>($rankTw[$ins->tw_sekarang]+1),
                                'num_of_growth_tw'=>$ins->num_of_growth_tw,
                                'fb'=>2,
                                'growth_fb'=>$ins->growth_fb." %",
                                'fb_sekarang'=>$ins->fb_sekarang,
                                'rank_fb'=>($rankFb[$ins->fb_sekarang]+1),
                                'num_of_growth_fb'=>$ins->num_of_growth_fb,
                                'ig'=>3,
                                'growth_ig'=>$ins->growth_ig,
                                'ig_sekarang'=>$ins->ig_sekarang,
                                'rank_ig'=>($rankIg[$ins->ig_sekarang]+1),
                                'num_of_growth_ig'=>$ins->num_of_growth_ig
                            )
                        );
                    }
                }
            }else{
                $data[]=array(
                    'id'=>$row->id,
                    'unit_name'=>$row->unit_name,
                    'follower'=>array(
                        'tw'=>1,
                        'growth_twitter'=>$row->growth_tw." %",
                        'tw_sekarang'=>$row->tw_sekarang,
                        'rank_tw'=>($rankTw[$row->tw_sekarang] + 1),
                        'num_of_growth_tw'=>$row->num_of_growth_tw,
                        'fb'=>2,
                        'growth_fb'=>$row->growth_fb." %",
                        'fb_sekarang'=>$row->fb_sekarang,
                        'rank_fb'=>($rankFb[$row->fb_sekarang] + 1),
                        'num_of_growth_fb'=>$row->num_of_growth_fb,
                        'ig'=>3,
                        'growth_ig'=>$row->growth_ig." %",
                        'ig_sekarang'=>$row->ig_sekarang,
                        'rank_ig'=>($rankIg[$row->ig_sekarang] + 1),
                        'num_of_growth_ig'=>$row->num_of_growth_ig
                    )
                );
            }
        }

        return $data;
    }

    public function rank_growth_from_yesterday_all_tv(Request $request){
        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }else{
            $sekarang=date('Y-m-d');
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }


        $rankOfOfficialAccountAllTvByFollowers=\DB::select("select b.id, b.unit_name,d.tanggal,
            sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) as tw_kemarin,
            sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) as tw_sekarang,
            (sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) - 1) as growth_tw,
            (sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_tw,
            sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) as fb_kemarin,
            sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) as fb_sekarang,
            (sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) - 1) as growth_fb,
            (sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_fb,
            sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) as ig_kemarin,
            sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) as ig_sekarang,
            (sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) - 1) as growth_ig,
            (sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_ig
            FROM business_unit b
            left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='corporate'
            left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal BETWEEN '$kemarin' and '$sekarang'
            group by b.id");

        $tambahanInews=\DB::select("select ifnull(a.id,'TOTAL') as id, a.business_unit_id,
            d.group_unit_id, a.program_name,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) as tw_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) as tw_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) -1)*100 as growth_tw,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0))) as num_of_growth_tw,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) as fb_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) as fb_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) -1)*100 as growth_fb,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0))) as num_of_growth_fb,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) as ig_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) as ig_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) -1)*100 as growth_ig,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0))) as num_of_growth_ig
            from program_unit a 
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
            left join business_unit d on d.id=a.business_unit_id
            where a.id in (89, 101, 95, 87)
            group by a.id
            with ROLLUP");

        return view('sosmed.view.rank_growth_from_yesterday_all_tv')
            ->with('kemarin',$kemarin)
            ->with('sekarang',$sekarang)
            ->with('rankOfOfficialAccountAllTvByFollowers',$rankOfOfficialAccountAllTvByFollowers)
            ->with('tambahanInews',$tambahanInews);
    }

    public function rank_growth_from_yesterday_group(Request $request){
        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }else{
            $sekarang=date('Y-m-d');
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }


        $group=\DB::select("select a.id, a.group_name,d.tanggal,
            sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) as tw_kemarin,
            sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) as tw_sekarang,
            (sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) - 1) as growth_tw,
            (sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_tw,
            sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) as fb_kemarin,
            sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) as fb_sekarang,
            (sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) - 1) as growth_fb,
            (sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_fb,
            sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) as ig_kemarin,
            sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) as ig_sekarang,
            (sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) - 1) as growth_ig,
            (sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_ig
            FROM group_unit a 
            left join business_unit b on b.group_unit_id=a.id
            left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='corporate'
            left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal BETWEEN '$kemarin' and '$sekarang'
            group by a.id");

        $data=array();

        $arrTw=array();
        $arrFb=array();
        $arrIg=array();
        foreach($group as $k){
            array_push($arrTw,$k->tw_sekarang);
            array_push($arrFb,$k->fb_sekarang);
            array_push($arrIg,$k->ig_sekarang);
        }
        $rankTw=$arrTw;
        $rankFb=$arrFb;
        $rankIg=$arrIg;

        rsort($rankTw);
        rsort($rankFb);
        rsort($rankIg);

        $rankTw=array_flip($rankTw);
        $rankFb=array_flip($rankFb);
        $rankIg=array_flip($rankIg);

        foreach($group as $row){
            $data[]=array(
                'id'=>$row->id,
                'group_name'=>$row->group_name,
                'follower'=>array(
                    'num_of_tw'=>$row->num_of_growth_tw,
                    'growth_twitter'=>$row->growth_tw." %",
                    'rank_tw'=>($rankTw[$row->tw_sekarang] + 1),
                    'num_of_fb'=>$row->num_of_growth_fb,
                    'growth_fb'=>$row->growth_fb." %",
                    'rank_fb'=>($rankFb[$row->fb_sekarang] + 1),
                    'num_of_ig'=>$row->num_of_growth_ig,
                    'growth_ig'=>$row->growth_ig." %",
                    'rank_ig'=>($rankIg[$row->ig_sekarang] + 1)
                )
            );
        }

        return $data;
    }

    public function rank_overall_account_all_tv_by_total_followers(Request $request){
        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }else{
            $sekarang=date('Y-m-d');
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }

        if($request->has('group')){
            $group="where a.group_unit_id='".$request->input('group')."'";
        }else{
            $group="";
        }

        $summary=\DB::select("select terjadi.id, terjadi.unit_name,
            sum(terjadi.kemarin_tw) as total_kemarin_tw,
            sum(terjadi.sekarang_tw) as total_sekarang_tw,
            (sum(terjadi.sekarang_tw)/sum(terjadi.kemarin_tw)-1) as growth_tw,
            (sum(terjadi.sekarang_tw)-sum(terjadi.kemarin_tw)) as num_of_growth_tw,
            sum(terjadi.kemarin_fb) as total_kemarin_fb,
            sum(terjadi.sekarang_fb) as total_sekarang_fb,
            (sum(terjadi.sekarang_fb)/sum(terjadi.kemarin_fb)-1) as growth_fb,
            (sum(terjadi.sekarang_fb)-sum(terjadi.kemarin_fb)) as num_of_growth_fb,
            sum(terjadi.kemarin_ig) as total_kemarin_ig,
            sum(terjadi.sekarang_ig) as total_sekarang_ig,
            (sum(terjadi.sekarang_ig)/sum(terjadi.kemarin_ig)-1) as growth_ig ,
            (sum(terjadi.sekarang_ig)-sum(terjadi.kemarin_ig)) as num_of_growth_ig
            from (
                select a.id, a.unit_name,c.unit_sosmed_name ,d.tanggal,  
            sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) as kemarin_tw,
            sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) as sekarang_tw,
            sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) as kemarin_fb,
            sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) as sekarang_fb,
            sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) as kemarin_ig,
            sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) as sekarang_ig
            from 
            business_unit a 
            left join program_unit b on b.business_unit_id=a.id
            left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='program'
            left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal between '$kemarin' and '$sekarang'
            $group
            group by a.id
            union all 
            select a.id, a.unit_name,b.unit_sosmed_name ,c.tanggal,  
            sum(if(b.sosmed_id=1 and c.tanggal='$kemarin',c.follower,0)) as kemarin_tw,
            sum(if(b.sosmed_id=1 and c.tanggal='$sekarang',c.follower,0)) as sekarang_tw,
            sum(if(b.sosmed_id=2 and c.tanggal='$kemarin',c.follower,0)) as kemarin_fb,
            sum(if(b.sosmed_id=2 and c.tanggal='$sekarang',c.follower,0)) as sekarang_fb,
            sum(if(b.sosmed_id=3 and c.tanggal='$kemarin',c.follower,0)) as kemarin_ig,
            sum(if(b.sosmed_id=3 and c.tanggal='$sekarang',c.follower,0)) as sekarang_ig
            from 
            business_unit a 
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='corporate'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal between '$kemarin' and '$sekarang'
            $group
            group by a.id ) as terjadi
            group by terjadi.id");

        $arrTw=array();
        $arrFb=array();
        $arrIg=array();
        foreach($summary as $k){
            array_push($arrTw,$k->total_sekarang_tw);
            array_push($arrFb,$k->total_sekarang_fb);
            array_push($arrIg,$k->total_sekarang_ig);
        }
        $rankTw=$arrTw;
        $rankFb=$arrFb;
        $rankIg=$arrIg;

        rsort($rankTw);
        rsort($rankFb);
        rsort($rankIg);

        $rankTw=array_flip($rankTw);
        $rankFb=array_flip($rankFb);
        $rankIg=array_flip($rankIg);

        $data=array();
        foreach($summary as $key=>$val){
            $follower=array(
                'tw'=>array(
                    'growth'=>$val->growth_tw,
                    'num_of_growth'=>$val->num_of_growth_tw,
                    'total'=>$val->total_sekarang_tw,
                    'rank'=>($rankTw[$val->total_sekarang_tw] + 1)
                ),
                'fb'=>array(
                    'growth'=>$val->growth_fb,
                    'num_of_growth'=>$val->num_of_growth_fb,
                    'total'=>$val->total_sekarang_fb,
                    'rank'=>($rankFb[$val->total_sekarang_fb] + 1)
                ),
                'ig'=>array(
                    'growth'=>$val->growth_ig,
                    'num_of_growth'=>$val->num_of_growth_ig,
                    'total'=>$val->total_sekarang_ig,
                    'rank'=>($rankIg[$val->total_sekarang_ig] + 1)
                )
            );

            $data[]=array(
                'id'=>$val->id,
                'unit_name'=>$val->unit_name,
                'followers'=>$follower
            );
        }

        return $data;
    }

    public function rank_of_overall_all_group_by_follower(Request $request){
        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }else{
            $sekarang=date('Y-m-d');
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }

        $summary=\DB::select("select a.id,a.group_name,  
            sum(if(e.tanggal='$kemarin' and c.sosmed_id=1,e.follower,0)) as kemarin_tw,
            sum(if(e.tanggal='$sekarang' and c.sosmed_id=1,e.follower,0)) as sekarang_tw,
            (sum(if(e.tanggal='$sekarang' and c.sosmed_id=1,e.follower,0)) / sum(if(e.tanggal='$kemarin' and c.sosmed_id=1,e.follower,0)) - 1) as growth_tw,
            (sum(if(e.tanggal='$sekarang' and c.sosmed_id=1,e.follower,0)) - sum(if(e.tanggal='$kemarin' and c.sosmed_id=1,e.follower,0))) as num_growth_tw,
            sum(if(e.tanggal='$kemarin' and c.sosmed_id=2,e.follower,0)) as kemarin_fb,
            sum(if(e.tanggal='$sekarang' and c.sosmed_id=2,e.follower,0)) as sekarang_fb,
            (sum(if(e.tanggal='$sekarang' and c.sosmed_id=2,e.follower,0)) / sum(if(e.tanggal='$kemarin' and c.sosmed_id=2,e.follower,0)) - 1) as growth_fb,
            (sum(if(e.tanggal='$sekarang' and c.sosmed_id=2,e.follower,0)) - sum(if(e.tanggal='$kemarin' and c.sosmed_id=2,e.follower,0))) as num_growth_fb,
            sum(if(e.tanggal='$kemarin' and c.sosmed_id=3,e.follower,0)) as kemarin_ig,
            sum(if(e.tanggal='$sekarang' and c.sosmed_id=3,e.follower,0)) as sekarang_ig,
            (sum(if(e.tanggal='$sekarang' and c.sosmed_id=3,e.follower,0)) / sum(if(e.tanggal='$kemarin' and c.sosmed_id=3,e.follower,0)) - 1) as growth_ig,
            (sum(if(e.tanggal='$sekarang' and c.sosmed_id=3,e.follower,0)) - sum(if(e.tanggal='$kemarin' and c.sosmed_id=3,e.follower,0))) as num_growth_ig
            from group_unit a 
            left join business_unit b on b.group_unit_id=a.id
            left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='corporate'
            left join sosmed d on d.id=c.sosmed_id
            left join unit_sosmed_follower e on e.unit_sosmed_id=c.id and e.tanggal between '$kemarin' and '$sekarang'
            group by a.id");   

        $arrTw=array();
        $arrFb=array();
        $arrIg=array();
        foreach($summary as $k){
            array_push($arrTw,$k->sekarang_tw);
            array_push($arrFb,$k->sekarang_fb);
            array_push($arrIg,$k->sekarang_ig);
        }
        $rankTw=$arrTw;
        $rankFb=$arrFb;
        $rankIg=$arrIg;

        rsort($rankTw);
        rsort($rankFb);
        rsort($rankIg);

        $rankTw=array_flip($rankTw);
        $rankFb=array_flip($rankFb);
        $rankIg=array_flip($rankIg);


        $data=array();

        foreach($summary as $key=>$val){
            $follower=array(
                'tw'=>array(
                    'growth'=>$val->growth_tw,
                    'num_of_growth'=>$val->num_growth_tw,
                    'total'=>$val->sekarang_tw,
                    'rank'=>($rankTw[$val->sekarang_tw] + 1)
                ),
                'fb'=>array(
                    'growth'=>$val->growth_fb,
                    'num_of_growth'=>$val->num_growth_fb,
                    'total'=>$val->sekarang_fb,
                    'rank'=>($rankFb[$val->sekarang_fb] + 1)
                ),
                'ig'=>array(
                    'growth'=>$val->growth_ig,
                    'num_of_growth'=>$val->num_growth_ig,
                    'total'=>$val->sekarang_ig,
                    'rank'=>($rankIg[$val->sekarang_ig] + 1)
                )
            );

            $data[]=array(
                'id'=>$val->id,
                'group_name'=>$val->group_name,
                'followers'=>$follower
            );
        }

        return $data;
    }

    public function rank_of_official_account_among_4tv(Request $request){
        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }else{
            $sekarang=date('Y-m-d');
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }

        if($request->has('group')){
            $group="where a.group_unit_id='".$request->input('group')."'";
        }else{
            $group="";
        }

        $summary=\DB::select("select a.id, a.unit_name,c.unit_sosmed_name ,d.tanggal,  
            sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) as kemarin_tw,
            sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) as sekarang_tw,
            ( sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) - 1) as growth_tw,
            ( sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_tw,
            sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) as kemarin_fb,
            sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) as sekarang_fb,
            ( sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) - 1) as growth_fb,
            ( sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_fb,
            sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) as kemarin_ig,
            sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) as sekarang_ig,
            ( sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) - 1) as growth_ig,
            ( sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_ig
            from 
            business_unit a 
            left join program_unit b on b.business_unit_id=a.id
            left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='program'
            left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal between '$kemarin' and '$sekarang'
            $group
            group by a.id");
        
        $arrTw=array();
        $arrFb=array();
        $arrIg=array();
        foreach($summary as $k){
            array_push($arrTw,$k->sekarang_tw);
            array_push($arrFb,$k->sekarang_fb);
            array_push($arrIg,$k->sekarang_ig);
        }
        $rankTw=$arrTw;
        $rankFb=$arrFb;
        $rankIg=$arrIg;

        rsort($rankTw);
        rsort($rankFb);
        rsort($rankIg);

        $rankTw=array_flip($rankTw);
        $rankFb=array_flip($rankFb);
        $rankIg=array_flip($rankIg);

        $data=array();
        foreach($summary as $key=>$val){
            $follower=array(
                'tw'=>array(
                    'growth'=>$val->growth_tw,
                    'num_of_growth'=>$val->num_of_growth_tw,
                    'total'=>$val->sekarang_tw,
                    'rank'=>($rankTw[$val->sekarang_tw] + 1)
                ),
                'fb'=>array(
                    'growth'=>$val->growth_fb,
                    'num_of_growth'=>$val->num_of_growth_fb,
                    'total'=>$val->sekarang_fb,
                    'rank'=>($rankFb[$val->sekarang_fb] + 1)
                ),
                'ig'=>array(
                    'growth'=>$val->growth_ig,
                    'num_of_growth'=>$val->num_of_growth_ig,
                    'total'=>$val->sekarang_ig,
                    'rank'=>($rankIg[$val->sekarang_ig] + 1)
                )
            );

            $data[]=array(
                'id'=>$val->id,
                'unit_name'=>$val->unit_name,
                'followers'=>$follower
            );
        }

        return $data;
    }

    public function pdf_rank_for_social_media_all_tv(Request $request){
        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));

            if($request->has('pilih')){
                $pilih=$request->input('pilih');

                if($pilih=="on"){
                    if($request->has('kemarin')){
                        $kemarin=date('Y-m-d',strtotime($request->input('kemarin')));
                    }else{
                        $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
                    }
                }else{
                    $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
                }
            }else{
                $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
            }
            
        }else{
            $sekarang=date('Y-m-d');
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }

        $data['sekarang']=$sekarang;
        $data['kemarin']=$kemarin;


        $data['rankOfOfficialAccountAllGroupByFollowers']=\DB::select("select a.id, a.group_name,d.tanggal,
            sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) as tw_kemarin,
            sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) as tw_sekarang,
            ((sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) - 1) * 100) as growth_tw,
            (sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_tw,
            sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) as fb_kemarin,
            sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) as fb_sekarang,
            ((sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) - 1) * 100) as growth_fb,
            (sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_fb,
            sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) as ig_kemarin,
            sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) as ig_sekarang,
            ((sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) - 1) * 100) as growth_ig,
            (sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_ig
            FROM group_unit a 
            left join business_unit b on b.group_unit_id=a.id
            left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='corporate'
            left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal BETWEEN '$kemarin' and '$sekarang'
            group by a.id");

        $data['rankOfOfficialAccountAllTvByFollowers']=\DB::select("select b.id, b.unit_name,d.tanggal,
            sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) as tw_kemarin,
            sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) as tw_sekarang,
            ((sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) -1) * 100) as growth_tw,
            (sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_tw,
            sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) as fb_kemarin,
            sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) as fb_sekarang,
            ((sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) -1) * 100) as growth_fb,
            (sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_fb,
            sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) as ig_kemarin,
            sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) as ig_sekarang,
            ((sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) -1) * 100) as growth_ig,
            (sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_ig
            FROM business_unit b
            left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='corporate'
            left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal BETWEEN '$kemarin' and '$sekarang'
            group by b.id");

        $data['rankOverallAccountGroup']=\DB::select("select terjadi.group_unit_id,terjadi.group_name,
            sum(terjadi.tw_kemarin) as total_tw_kemarin,
            sum(terjadi.tw_sekarang) as total_tw_sekarang,
            ((sum(terjadi.tw_sekarang) / sum(terjadi.tw_kemarin) - 1) * 100) as total_growth_tw,
            (sum(terjadi.tw_sekarang) - sum(terjadi.tw_kemarin)) as total_num_of_growth_tw,
            sum(terjadi.fb_kemarin) as total_fb_kemarin,
            sum(terjadi.fb_sekarang) as total_fb_sekarang,
            ((sum(terjadi.fb_sekarang) / sum(terjadi.fb_kemarin) - 1) * 100) as total_growth_fb,
            (sum(terjadi.fb_sekarang) - sum(terjadi.fb_kemarin)) as total_num_of_growth_fb,
            sum(terjadi.ig_kemarin) as total_ig_kemarin,
            sum(terjadi.ig_sekarang) as total_ig_sekarang,
            ((sum(terjadi.ig_sekarang) / sum(terjadi.ig_kemarin) - 1) * 100) as total_growth_ig,
            (sum(terjadi.ig_sekarang) - sum(terjadi.ig_kemarin)) as total_num_of_growth_ig
                from (
                    select a.id, a.unit_name,c.unit_sosmed_name ,d.tanggal,  
                    a.group_unit_id,e.group_name,
                    sum(if(d.tanggal='$kemarin' and c.sosmed_id=1,d.follower,0)) as tw_kemarin,
                    sum(if(d.tanggal='$sekarang' and c.sosmed_id=1,d.follower,0)) as tw_sekarang,
                    sum(if(d.tanggal='$kemarin' and c.sosmed_id=2,d.follower,0)) as fb_kemarin,
                    sum(if(d.tanggal='$sekarang' and c.sosmed_id=2,d.follower,0)) as fb_sekarang,
                    sum(if(d.tanggal='$kemarin' and c.sosmed_id=3,d.follower,0)) as ig_kemarin,
                    sum(if(d.tanggal='$sekarang' and c.sosmed_id=3,d.follower,0)) as ig_sekarang
                    from 
                    business_unit a 
                    left join program_unit b on b.business_unit_id=a.id
                    left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='program'
                    left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal BETWEEN '$kemarin' and '$sekarang'
                    left join group_unit e on e.id=a.group_unit_id
                    group by a.id
                    union all 
                    select a.id, a.unit_name,b.unit_sosmed_name ,c.tanggal,  
                    a.group_unit_id,e.group_name,
                    sum(if(c.tanggal='$kemarin' and b.sosmed_id=1,c.follower,0)) as tw_kemarin,
                    sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) as tw_sekarang,
                    sum(if(c.tanggal='$kemarin' and b.sosmed_id=2,c.follower,0)) as fb_kemarin,
                    sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) as fb_sekarang,
                    sum(if(c.tanggal='$kemarin' and b.sosmed_id=3,c.follower,0)) as ig_kemarin,
                    sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) as ig_sekarang
                    from 
                    business_unit a 
                    left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='corporate'
                    left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
                    left join group_unit e on e.id=a.group_unit_id
                    group by a.id 
                ) as terjadi
            group by terjadi.group_unit_id");

        $data['rankOverallAccountAllTv']=\DB::select("select terjadi.id, terjadi.group_unit_id,terjadi.unit_name,
            sum(terjadi.tw_kemarin) as total_tw_kemarin,
            sum(terjadi.tw_sekarang) as total_tw_sekarang,
            ((sum(terjadi.tw_sekarang) / sum(terjadi.tw_kemarin) - 1) * 100) as total_growth_tw,
            (sum(terjadi.tw_sekarang) - sum(terjadi.tw_kemarin)) as total_num_of_growth_tw,
            sum(terjadi.fb_kemarin) as total_fb_kemarin,
            sum(terjadi.fb_sekarang) as total_fb_sekarang,
            ((sum(terjadi.fb_sekarang) / sum(terjadi.fb_kemarin) - 1) * 100) as total_growth_fb,
            (sum(terjadi.fb_sekarang) - sum(terjadi.fb_kemarin)) as total_num_of_growth_fb,
            sum(terjadi.ig_kemarin) as total_ig_kemarin,
            sum(terjadi.ig_sekarang) as total_ig_sekarang,
            ((sum(terjadi.ig_sekarang) / sum(terjadi.ig_kemarin) - 1) * 100) as total_growth_ig,
            (sum(terjadi.ig_sekarang) - sum(terjadi.ig_kemarin)) as total_num_of_growth_ig
            from (
                select a.id, a.unit_name,c.unit_sosmed_name ,d.tanggal,  
                a.group_unit_id,
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=1,d.follower,0)) as tw_kemarin,
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=1,d.follower,0)) as tw_sekarang,
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=2,d.follower,0)) as fb_kemarin,
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=2,d.follower,0)) as fb_sekarang,
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=3,d.follower,0)) as ig_kemarin,
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=3,d.follower,0)) as ig_sekarang
                from 
                business_unit a 
                left join program_unit b on b.business_unit_id=a.id
                left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='program'
                left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal BETWEEN '$kemarin' and '$sekarang'
                group by a.id
                union all 
                select a.id, a.unit_name,b.unit_sosmed_name ,c.tanggal,  
                a.group_unit_id,
                sum(if(c.tanggal='$kemarin' and b.sosmed_id=1,c.follower,0)) as tw_kemarin,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) as tw_sekarang,
                sum(if(c.tanggal='$kemarin' and b.sosmed_id=2,c.follower,0)) as fb_kemarin,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) as fb_sekarang,
                sum(if(c.tanggal='$kemarin' and b.sosmed_id=3,c.follower,0)) as ig_kemarin,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) as ig_sekarang
                from 
                business_unit a 
                left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='corporate'
                left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
                group by a.id 
            ) as terjadi
            group by terjadi.id");

        $data['groupOthers']=\DB::select("select 'corporate' as urut,a.id, a.group_unit_id, a.unit_name, 
            b.type_sosmed, b.unit_sosmed_name, c.tanggal, 
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=1,c.follower,0)) as tw_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) as tw_sekarang,
            ( sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=1,c.follower,0)) - 1) * 100 as growth_tw,
            ( sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=1,c.follower,0))) as num_of_growth_tw,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=2,c.follower,0)) as fb_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) as fb_sekarang,
            ( sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=2,c.follower,0)) - 1) * 100 as growth_fb,
            ( sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=2,c.follower,0))) as num_of_growth_fb,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=3,c.follower,0)) as ig_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) as ig_sekarang,
            ( sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=3,c.follower,0)) - 1) * 100 as growth_ig,
            ( sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=3,c.follower,0))) as num_of_growth_ig
            from business_unit a
            left join unit_sosmed as b on b.business_program_unit=a.id and b.type_sosmed='corporate'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal between '$kemarin' and '$sekarang'
            where a.group_unit_id=5
            group by a.id");

        $data['tambahanInews']=\DB::select("select ifnull(a.id,'TOTAL') as id, a.business_unit_id,
            d.group_unit_id, a.program_name,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) as tw_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) as tw_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) -1)*100 as growth_tw,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0))) as num_of_growth_tw,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) as fb_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) as fb_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) -1)*100 as growth_fb,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0))) as num_of_growth_fb,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) as ig_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) as ig_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) -1)*100 as growth_ig,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0))) as num_of_growth_ig
            from program_unit a 
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
            left join business_unit d on d.id=a.business_unit_id
            where a.id in (89, 101, 95, 87)
            group by a.id
            with ROLLUP");

        $data['tambahanOverAllTvOthers']=\DB::select("select terjadi.id, terjadi.unit_name, terjadi.group_id, terjadi.group_name,
            sum(terjadi.tw_kemarin) as total_tw_kemarin,
            sum(terjadi.tw_sekarang) as total_tw_sekarang,
            ((sum(terjadi.tw_sekarang) / sum(terjadi.tw_kemarin) - 1) * 100) as total_growth_tw,
            (sum(terjadi.tw_sekarang) - sum(terjadi.tw_kemarin)) as total_num_of_growth_tw,
            sum(terjadi.fb_kemarin) as total_fb_kemarin,
            sum(terjadi.fb_sekarang) as total_fb_sekarang,
            ((sum(terjadi.fb_sekarang) / sum(terjadi.fb_kemarin) - 1) * 100) as total_growth_fb,
            (sum(terjadi.fb_sekarang) - sum(terjadi.fb_kemarin)) as total_num_of_growth_fb,
            sum(terjadi.ig_kemarin) as total_ig_kemarin,
            sum(terjadi.ig_sekarang) as total_ig_sekarang,
            ((sum(terjadi.ig_sekarang) / sum(terjadi.ig_kemarin) - 1) * 100) as total_growth_ig,
            (sum(terjadi.ig_sekarang) - sum(terjadi.ig_kemarin)) as total_num_of_growth_ig 
            from
            (
                select ifnull(a.id,'SUBTOTAL') as id, a.unit_name,  
                ifnull(a.group_unit_id,'TOTAL') as group_id,e.group_name,
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=1,d.follower,0)) as tw_kemarin,
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=1,d.follower,0)) as tw_sekarang,
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=2,d.follower,0)) as fb_kemarin,
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=2,d.follower,0)) as fb_sekarang,
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=3,d.follower,0)) as ig_kemarin,
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=3,d.follower,0)) as ig_sekarang
                from 
                business_unit a 
                left join program_unit b on b.business_unit_id=a.id
                left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='program'
                left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal BETWEEN '$kemarin' and '$sekarang'
                left join group_unit as e on e.id=a.group_unit_id
                where a.group_unit_id=5
                group by a.group_unit_id,a.id
                union all 
                select ifnull(a.id,'SUBTOTAL') as id, a.unit_name, 
                ifnull(a.group_unit_id,'TOTAL') as group_id,e.group_name,
                sum(if(c.tanggal='$kemarin' and b.sosmed_id=1,c.follower,0)) as tw_kemarin,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) as tw_sekarang,
                sum(if(c.tanggal='$kemarin' and b.sosmed_id=2,c.follower,0)) as fb_kemarin,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) as fb_sekarang,
                sum(if(c.tanggal='$kemarin' and b.sosmed_id=3,c.follower,0)) as ig_kemarin,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) as ig_sekarang
                from 
                business_unit a 
                left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='corporate'
                left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
                left join group_unit as e on e.id=a.group_unit_id
                where a.group_unit_id=5
                group by a.group_unit_id,a.id
            ) as terjadi
            group by terjadi.group_id,terjadi.id");

        $pdf = \PDF::loadView('sosmed.pdf.rank_for_social_media_all_tv', $data)
            ->setPaper('a4', 'landscape')->setWarnings(false);

        return $pdf->stream();
    }

    public function pdf_sosmed_daily_report(Request $request){
        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));

            if($request->has('pilih')){
                $pilih=$request->input('pilih');

                if($pilih=="on"){
                    if($request->has('kemarin')){
                        $kemarin=date('Y-m-d',strtotime($request->input('kemarin')));
                    }else{
                        $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
                    }
                }else{
                    $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
                }
            }else{
                $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
            }

        }else{
            $sekarang=date('Y-m-d');
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }

        if($request->has('group')){
            $group=$request->input('group');
        }else{
            $group=1;
        }

        $data['sekarang']=$sekarang;
        $data['kemarin']=$kemarin;

        $data['targetVsAch']=\DB::select("select a.id, a.group_unit_id, a.unit_name, b.target_use, 
            sum(if(b.sosmed_id=1, c.target,0)) as target_tw,
            sum(if(d.tanggal='$sekarang' and b.sosmed_id=1, d.follower,0)) as follower_tw,
            ( sum(if(d.tanggal='$sekarang' and b.sosmed_id=1, d.follower,0)) / sum(if(b.sosmed_id=1, c.target,0)) * 100) as acv_tw,
            sum(if(b.sosmed_id=2, c.target,0)) as target_fb,
            sum(if(d.tanggal='$sekarang' and b.sosmed_id=2, d.follower,0)) as follower_fb,
            ( sum(if(d.tanggal='$sekarang' and b.sosmed_id=2, d.follower,0)) / sum(if(b.sosmed_id=2, c.target,0)) * 100) as acv_fb,
            sum(if(b.sosmed_id=3, c.target,0)) as target_ig,
            sum(if(d.tanggal='$sekarang' and b.sosmed_id=3, d.follower,0)) as follower_ig,
            ( sum(if(d.tanggal='$sekarang' and b.sosmed_id=3, d.follower,0)) / sum(if(b.sosmed_id=3, c.target,0)) * 100) as acv_ig
            from business_unit a
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='corporate'
            left join unit_sosmed_target c on c.unit_sosmed_id=b.id
            left join unit_sosmed_follower d on d.unit_sosmed_id=b.id and d.tanggal='$sekarang'
            where a.group_unit_id='$group'
            GROUP by a.id");

        $data['officialTv']=\DB::select("select ifnull(a.id,'SUBTOTAL') as id, a.unit_name,  
            ifnull(a.group_unit_id,'TOTAL') as group_id,e.group_name,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=1,c.follower,0)) as tw_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) as tw_sekarang,
            ((sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=1,c.follower,0)) -1) * 100) as growth_tw,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=2,c.follower,0)) as fb_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) as fb_sekarang,
            ((sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=2,c.follower,0)) - 1) * 100) as growth_fb,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=3,c.follower,0)) as ig_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) as ig_sekarang,
            ((sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=3,c.follower,0)) - 1) * 100) as growth_ig
            from 
            business_unit a 
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='corporate'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
            left join group_unit e on e.id=a.group_unit_id
            group by a.group_unit_id,a.id
            with ROLLUP");

        $data['overallOfficialTv']=\DB::select("select terjadi.id, terjadi.unit_name, terjadi.group_id, terjadi.group_name,
            sum(terjadi.tw_kemarin) as total_tw_kemarin,
            sum(terjadi.tw_sekarang) as total_tw_sekarang,
            ((sum(terjadi.tw_sekarang) / sum(terjadi.tw_kemarin) - 1) * 100) as total_growth_tw,
            (sum(terjadi.tw_sekarang) - sum(terjadi.tw_kemarin)) as total_num_of_growth_tw,
            sum(terjadi.fb_kemarin) as total_fb_kemarin,
            sum(terjadi.fb_sekarang) as total_fb_sekarang,
            ((sum(terjadi.fb_sekarang) / sum(terjadi.fb_kemarin) - 1) * 100) as total_growth_fb,
            (sum(terjadi.fb_sekarang) - sum(terjadi.fb_kemarin)) as total_num_of_growth_fb,
            sum(terjadi.ig_kemarin) as total_ig_kemarin,
            sum(terjadi.ig_sekarang) as total_ig_sekarang,
            ((sum(terjadi.ig_sekarang) / sum(terjadi.ig_kemarin) - 1) * 100) as total_growth_ig,
            (sum(terjadi.ig_sekarang) - sum(terjadi.ig_kemarin)) as total_num_of_growth_ig 
            from
            (
                select ifnull(a.id,'SUBTOTAL') as id, a.unit_name,  
                ifnull(a.group_unit_id,'TOTAL') as group_id,e.group_name,
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=1,d.follower,0)) as tw_kemarin,
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=1,d.follower,0)) as tw_sekarang,
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=2,d.follower,0)) as fb_kemarin,
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=2,d.follower,0)) as fb_sekarang,
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=3,d.follower,0)) as ig_kemarin,
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=3,d.follower,0)) as ig_sekarang
                from 
                business_unit a 
                left join program_unit b on b.business_unit_id=a.id
                left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='program'
                left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal BETWEEN '$kemarin' and '$sekarang'
                left join group_unit as e on e.id=a.group_unit_id
                group by a.group_unit_id,a.id
                with ROLLUP
                union all 
                select ifnull(a.id,'SUBTOTAL') as id, a.unit_name, 
                ifnull(a.group_unit_id,'TOTAL') as group_id,e.group_name,
                sum(if(c.tanggal='$kemarin' and b.sosmed_id=1,c.follower,0)) as tw_kemarin,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) as tw_sekarang,
                sum(if(c.tanggal='$kemarin' and b.sosmed_id=2,c.follower,0)) as fb_kemarin,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) as fb_sekarang,
                sum(if(c.tanggal='$kemarin' and b.sosmed_id=3,c.follower,0)) as ig_kemarin,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) as ig_sekarang
                from 
                business_unit a 
                left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='corporate'
                left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
                left join group_unit as e on e.id=a.group_unit_id
                group by a.group_unit_id,a.id
                with ROLLUP
            ) as terjadi
            group by terjadi.group_id,terjadi.id");

        /* official and program mnc group */
        $data['officialAndProgram']=\DB::select("select 'corporate' as urut,a.id, a.group_unit_id, a.unit_name, 
                b.type_sosmed, b.unit_sosmed_name, c.tanggal, 
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) as tw,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) as fb,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) as ig
                from business_unit a
                left join unit_sosmed as b on b.business_program_unit=a.id and b.type_sosmed='corporate'
                left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal='$sekarang'
                where a.group_unit_id='$group'
                group by a.id
                union all 
                select 'program' as urut,d.id, d.group_unit_id, d.unit_name, b.type_sosmed,a.program_name,c.tanggal, 
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) as tw,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) as fb,
                sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) as ig
                from program_unit a 
                left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
                left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal='$sekarang'
                left join business_unit d on d.id=a.business_unit_id
                where d.group_unit_id='$group'
                group by a.id
                union all 
                select 'total' as urut,semua.id, semua.group_unit_id, semua.unit_name, semua.type_sosmed, semua.unit_sosmed_name, semua.tanggal,
                    sum(tw) as total_tw,
                    sum(fb) as total_fb,
                    sum(ig) as total_ig
                    from (
                        select a.id,a.group_unit_id, a.unit_name, b.type_sosmed, b.unit_sosmed_name, c.tanggal, 
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=1,c.follower,0)) as tw,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=2,c.follower,0)) as fb,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=3,c.follower,0)) as ig
                        from business_unit a
                        left join unit_sosmed as b on b.business_program_unit=a.id and b.type_sosmed='corporate'
                        left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal='$sekarang'
                        where a.group_unit_id='$group'
                        group by a.id
                        union all 
                        select d.id,d.group_unit_id, d.unit_name, b.type_sosmed,a.program_name,c.tanggal, 
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) as tw,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) as fb,
                        sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) as ig
                        from program_unit a 
                        left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
                        left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal='$sekarang'
                        left join business_unit d on d.id=a.business_unit_id
                        where d.group_unit_id='$group'
                        group by a.id
                    ) as semua 
                group by semua.id
                order by id, urut,type_sosmed");

        $data['attachment']=\App\Models\Sosmed\Businessunit::select('id','unit_name')
                ->with(
                [
                    'sosmed',
                    'program',
                    'program.sosmed'
                ]
            )->get();

        $data['sosmed']=\App\Models\Sosmed\Sosmed::all();

        $data['tambahanInews']=\DB::select("select ifnull(a.id,'TOTAL') as id, a.business_unit_id,
            d.group_unit_id, a.program_name,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) as tw_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) as tw_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) -1)*100 as growth_tw,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) as fb_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) as fb_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) -1)*100 as growth_fb,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) as ig_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) as ig_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) -1)*100 as growth_ig
            from program_unit a 
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
            left join business_unit d on d.id=a.business_unit_id
            where a.id in (89, 101, 95, 87)
            group by a.id
            with ROLLUP");

        $pdf = \PDF::loadView('sosmed.pdf.sosmed_daily_report', $data)
            ->setPaper('a4', 'landscape')->setWarnings(false);

        return $pdf->stream();
    }

    public function highlight_of_official_account_all_tv(Request $request){
        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));

            if($request->has('pilih')){
                $pilih=$request->input('pilih');

                if($pilih=="on"){
                    if($request->has('kemarin')){
                        $kemarin=date('Y-m-d',strtotime($request->input('kemarin')));
                    }else{
                        $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
                    }
                }else{
                    $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
                }
            }else{
                $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
            }
        }else{
            $sekarang=date('Y-m-d');
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }

        $group=\DB::select("select b.id, b.unit_name,d.tanggal,
            sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) as tw_kemarin,
            sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) as tw_sekarang,
            (sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) -1) as growth_tw,
            (sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_tw,
            sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) as fb_kemarin,
            sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) as fb_sekarang,
            (sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) -1) as growth_fb,
            (sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_fb,
            sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) as ig_kemarin,
            sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) as ig_sekarang,
            (sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) -1) as growth_ig,
            (sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_ig
            FROM business_unit b
            left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='corporate'
            left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal BETWEEN '$kemarin' and '$sekarang'
            group by b.id");

        $tambahanInews=\DB::select("select ifnull(a.id,'TOTAL') as id, a.business_unit_id,
            d.group_unit_id, a.program_name,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) as tw_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) as tw_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) -1)*100 as growth_tw,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0))) as num_of_growth_tw,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) as fb_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) as fb_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) -1)*100 as growth_fb,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0))) as num_of_growth_fb,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) as ig_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) as ig_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) -1)*100 as growth_ig,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0))) as num_of_growth_ig
            from program_unit a 
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
            left join business_unit d on d.id=a.business_unit_id
            where a.id in (89, 101, 95, 87)
            group by a.id
            with ROLLUP");

        $data=array();

        $arrTw=array();
        $arrFb=array();
        $arrIg=array();
        foreach($group as $k){
            if($k->id==4){
                foreach($tambahanInews as $in){
                    if($in->id=="TOTAL"){
                        array_push($arrTw,$in->tw_sekarang);
                        array_push($arrFb,$in->fb_sekarang);
                        array_push($arrIg,$in->ig_sekarang);        
                    }
                }
            }else{
                array_push($arrTw,$k->tw_sekarang);
                array_push($arrFb,$k->fb_sekarang);
                array_push($arrIg,$k->ig_sekarang);
            }
        }
        $rankTw=$arrTw;
        $rankFb=$arrFb;
        $rankIg=$arrIg;

        rsort($rankTw);
        rsort($rankFb);
        rsort($rankIg);

        $rankTw=array_flip($rankTw);
        $rankFb=array_flip($rankFb);
        $rankIg=array_flip($rankIg);

        foreach($group as $row){
            if($row->id==4){
                foreach($tambahanInews as $ins){
                    if($ins->id=="TOTAL"){
                        $data[]=array(
                            'id'=>$row->id,
                            'unit_name'=>$row->unit_name,
                            'follower'=>array(
                                'tw'=>1,
                                'growth_twitter'=>$ins->growth_tw." %",
                                'tw_sekarang'=>$ins->tw_sekarang,
                                'rank_tw'=>($rankTw[$ins->tw_sekarang]+1),
                                'num_of_growth_tw'=>$ins->num_of_growth_tw,
                                'fb'=>2,
                                'growth_fb'=>$ins->growth_fb." %",
                                'fb_sekarang'=>$ins->fb_sekarang,
                                'rank_fb'=>($rankFb[$ins->fb_sekarang]+1),
                                'num_of_growth_fb'=>$ins->num_of_growth_fb,
                                'ig'=>3,
                                'growth_ig'=>$ins->growth_ig,
                                'ig_sekarang'=>$ins->ig_sekarang,
                                'rank_ig'=>($rankIg[$ins->ig_sekarang]+1),
                                'num_of_growth_ig'=>$ins->num_of_growth_ig
                            )
                        );
                    }
                }
            }else{
                $data[]=array(
                    'id'=>$row->id,
                    'unit_name'=>$row->unit_name,
                    'follower'=>array(
                        'tw'=>1,
                        'growth_twitter'=>$row->growth_tw." %",
                        'tw_sekarang'=>$row->tw_sekarang,
                        'rank_tw'=>($rankTw[$row->tw_sekarang] + 1),
                        'num_of_growth_tw'=>$row->num_of_growth_tw,
                        'fb'=>2,
                        'growth_fb'=>$row->growth_fb." %",
                        'fb_sekarang'=>$row->fb_sekarang,
                        'rank_fb'=>($rankFb[$row->fb_sekarang] + 1),
                        'num_of_growth_fb'=>$row->num_of_growth_fb,
                        'ig'=>3,
                        'growth_ig'=>$row->growth_ig." %",
                        'ig_sekarang'=>$row->ig_sekarang,
                        'rank_ig'=>($rankIg[$row->ig_sekarang] + 1),
                        'num_of_growth_ig'=>$row->num_of_growth_ig
                    )
                );
            }
        }

        return $data;
    }

    public function highlight_group_official_account_by_total_followers(Request $request){
        $allsosmed=\App\Models\Sosmed\Sosmed::select('id','sosmed_name')->get();

        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));

            if($request->has('pilih')){
                $pilih=$request->input('pilih');

                if($pilih=="on"){
                    if($request->has('kemarin')){
                        $kemarin=date('Y-m-d',strtotime($request->input('kemarin')));
                    }else{
                        $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
                    }
                }else{
                    $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
                }
            }else{
                $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
            }
        }else{
            $sekarang=date('Y-m-d');
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }

        if($request->has('group')){
            $group="where group_unit_id='".$request->input('group')."'";
        }

        $group=\DB::select("select a.id, a.group_name,d.tanggal,
            sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) as tw_kemarin,
            sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) as tw_sekarang,
            ((sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) - 1) * 100) as growth_tw,
            (sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_tw,
            sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) as fb_kemarin,
            sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) as fb_sekarang,
            ((sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) - 1) * 100) as growth_fb,
            (sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_fb,
            sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) as ig_kemarin,
            sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) as ig_sekarang,
            ((sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) / sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) - 1) * 100) as growth_ig,
            (sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) - sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0))) as num_of_growth_ig
            FROM group_unit a 
            left join business_unit b on b.group_unit_id=a.id
            left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='corporate'
            left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal BETWEEN '$kemarin' and '$sekarang'
            group by a.id");

        $tambahanInews=\DB::select("select ifnull(a.id,'TOTAL') as id, a.business_unit_id,
            d.group_unit_id, a.program_name,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) as tw_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) as tw_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) -1)*100 as growth_tw,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0))) as num_of_growth_tw,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) as fb_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) as fb_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) -1)*100 as growth_fb,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0))) as num_of_growth_fb,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) as ig_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) as ig_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) -1)*100 as growth_ig,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) - sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0))) as num_of_growth_ig
            from program_unit a 
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
            left join business_unit d on d.id=a.business_unit_id
            where a.id in (89, 101, 95, 87)
            group by a.id
            with ROLLUP");

        $data=array();

        $arrTw=array();
        $arrFb=array();
        $arrIg=array();
        foreach($group as $k){
            if($k->id==1){
                foreach($tambahanInews as $in){
                    if($in->id=="TOTAL"){
                        array_push($arrTw,($in->tw_sekarang+$k->tw_sekarang));
                        array_push($arrFb,($in->fb_sekarang+$k->fb_sekarang));
                        array_push($arrIg,($in->ig_sekarang+$k->ig_sekarang));        
                    }
                }
            }else{
                array_push($arrTw,$k->tw_sekarang);
                array_push($arrFb,$k->fb_sekarang);
                array_push($arrIg,$k->ig_sekarang);
            }
        }
        $rankTw=$arrTw;
        $rankFb=$arrFb;
        $rankIg=$arrIg;

        rsort($rankTw);
        rsort($rankFb);
        rsort($rankIg);

        $rankTw=array_flip($rankTw);
        $rankFb=array_flip($rankFb);
        $rankIg=array_flip($rankIg);

        foreach($group as $row){
            if($row->id==1){
                foreach($tambahanInews as $in){
                    if($in->id=="TOTAL"){
                        $data[]=array(
                            'id'=>$row->id,
                            'group_name'=>$row->group_name,
                            'follower'=>array(
                                'tw'=>1,
                                'tw_sekarang'=>($in->tw_sekarang+$row->tw_sekarang),
                                'tw_kemarin'=>($in->tw_kemarin+$row->tw_kemarin),
                                'growth_tw'=>(($in->tw_sekarang+$row->tw_sekarang) / ($in->tw_kemarin+$row->tw_kemarin) -1)*100,
                                'num_of_growth_tw'=>(($in->tw_sekarang+$row->tw_sekarang) - ($in->tw_kemarin+$row->tw_kemarin)),
                                'rank_tw'=>($rankTw[($in->tw_sekarang+$row->tw_sekarang)]+1),
                                'fb'=>2,
                                'fb_sekarang'=>($in->fb_sekarang+$row->fb_sekarang),
                                'fb_kemarin'=>($in->fb_kemarin+$row->fb_kemarin),
                                'growth_fb'=>(($in->fb_sekarang+$row->fb_sekarang) / ($in->fb_kemarin+$row->fb_kemarin) -1)*100,
                                'num_of_growth_fb'=>(($in->fb_sekarang+$row->fb_sekarang) - ($in->fb_kemarin+$row->fb_kemarin)),
                                'rank_fb'=>($rankFb[($in->fb_sekarang+$row->fb_sekarang)]+1),
                                'ig'=>3,
                                'ig_sekarang'=>($in->ig_sekarang+$row->ig_sekarang),
                                'ig_kemarin'=>($in->ig_kemarin+$row->ig_kemarin),
                                'growth_ig'=>(($in->ig_sekarang+$row->ig_sekarang) / ($in->ig_kemarin+$row->ig_kemarin) -1)*100,
                                'num_of_growth_ig'=>(($in->ig_sekarang+$row->ig_sekarang) - ($in->ig_kemarin+$row->ig_kemarin)),
                                'rank_ig'=>($rankIg[($in->ig_sekarang+$row->ig_sekarang)]+1)
                            )
                        );
                    }
                }
            }else{
                $data[]=array(
                    'id'=>$row->id,
                    'group_name'=>$row->group_name,
                    'follower'=>array(
                        'tw'=>1,
                        'tw_sekarang'=>$row->tw_sekarang,
                        'tw_kemarin'=>$row->tw_kemarin,
                        'growth_tw'=>$row->growth_tw,
                        'num_of_growth_tw'=>$row->num_of_growth_tw,
                        'rank_tw'=>($rankTw[$row->tw_sekarang]+1),
                        'fb'=>2,
                        'fb_sekarang'=>$row->fb_sekarang,
                        'fb_kemarin'=>$row->fb_kemarin,
                        'growth_fb'=>$row->growth_fb,
                        'num_of_growth_fb'=>$row->num_of_growth_fb,
                        'rank_fb'=>($rankFb[$row->fb_sekarang]+1),
                        'ig'=>3,
                        'ig_sekarang'=>$row->ig_sekarang,
                        'ig_kemarin'=>$row->ig_kemarin,
                        'growth_ig'=>$row->growth_ig,
                        'num_of_growth_ig'=>$row->num_of_growth_ig,
                        'rank_ig'=>($rankIg[$row->ig_sekarang]+1)
                    )
                );
            }
        }

        return $data;
    }

    public function highlight_group_overall_account(Request $request){
        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));

            if($request->has('pilih')){
                $pilih=$request->input('pilih');

                if($pilih=="on"){
                    if($request->has('kemarin')){
                        $kemarin=date('Y-m-d',strtotime($request->input('kemarin')));
                    }else{
                        $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
                    }
                }else{
                    $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
                }
            }else{
                $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
            }
        }else{
            $sekarang=date('Y-m-d');
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }

        if($request->has('group')){
            $group="where a.group_unit_id='".$request->input('group')."'";
        }else{
            $group="";
        }

        $summary=\DB::select("select terjadi.id, terjadi.unit_name,
            sum(terjadi.kemarin_tw) as total_kemarin_tw,
            sum(terjadi.sekarang_tw) as total_sekarang_tw,
            (sum(terjadi.sekarang_tw)/sum(terjadi.kemarin_tw)-1) as growth_tw,
            (sum(terjadi.sekarang_tw)-sum(terjadi.kemarin_tw)) as num_of_growth_tw,
            sum(terjadi.kemarin_fb) as total_kemarin_fb,
            sum(terjadi.sekarang_fb) as total_sekarang_fb,
            (sum(terjadi.sekarang_fb)/sum(terjadi.kemarin_fb)-1) as growth_fb,
            (sum(terjadi.sekarang_fb)-sum(terjadi.kemarin_fb)) as num_of_growth_fb,
            sum(terjadi.kemarin_ig) as total_kemarin_ig,
            sum(terjadi.sekarang_ig) as total_sekarang_ig,
            (sum(terjadi.sekarang_ig)/sum(terjadi.kemarin_ig)-1) as growth_ig ,
            (sum(terjadi.sekarang_ig)-sum(terjadi.kemarin_ig)) as num_of_growth_ig
            from (
                select a.id, a.unit_name,c.unit_sosmed_name ,d.tanggal,  
            sum(if(c.sosmed_id=1 and d.tanggal='$kemarin',d.follower,0)) as kemarin_tw,
            sum(if(c.sosmed_id=1 and d.tanggal='$sekarang',d.follower,0)) as sekarang_tw,
            sum(if(c.sosmed_id=2 and d.tanggal='$kemarin',d.follower,0)) as kemarin_fb,
            sum(if(c.sosmed_id=2 and d.tanggal='$sekarang',d.follower,0)) as sekarang_fb,
            sum(if(c.sosmed_id=3 and d.tanggal='$kemarin',d.follower,0)) as kemarin_ig,
            sum(if(c.sosmed_id=3 and d.tanggal='$sekarang',d.follower,0)) as sekarang_ig
            from 
            business_unit a 
            left join program_unit b on b.business_unit_id=a.id
            left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='program'
            left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal between '$kemarin' and '$sekarang'
            $group
            group by a.id
            union all 
            select a.id, a.unit_name,b.unit_sosmed_name ,c.tanggal,  
            sum(if(b.sosmed_id=1 and c.tanggal='$kemarin',c.follower,0)) as kemarin_tw,
            sum(if(b.sosmed_id=1 and c.tanggal='$sekarang',c.follower,0)) as sekarang_tw,
            sum(if(b.sosmed_id=2 and c.tanggal='$kemarin',c.follower,0)) as kemarin_fb,
            sum(if(b.sosmed_id=2 and c.tanggal='$sekarang',c.follower,0)) as sekarang_fb,
            sum(if(b.sosmed_id=3 and c.tanggal='$kemarin',c.follower,0)) as kemarin_ig,
            sum(if(b.sosmed_id=3 and c.tanggal='$sekarang',c.follower,0)) as sekarang_ig
            from 
            business_unit a 
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='corporate'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal between '$kemarin' and '$sekarang'
            $group
            group by a.id ) as terjadi
            group by terjadi.id");

        $arrTw=array();
        $arrFb=array();
        $arrIg=array();
        foreach($summary as $k){
            array_push($arrTw,$k->total_sekarang_tw);
            array_push($arrFb,$k->total_sekarang_fb);
            array_push($arrIg,$k->total_sekarang_ig);
        }
        $rankTw=$arrTw;
        $rankFb=$arrFb;
        $rankIg=$arrIg;

        rsort($rankTw);
        rsort($rankFb);
        rsort($rankIg);

        $rankTw=array_flip($rankTw);
        $rankFb=array_flip($rankFb);
        $rankIg=array_flip($rankIg);

        $data=array();
        foreach($summary as $key=>$val){
            $data[]=array(
                'id'=>$val->id,
                'unit_name'=>$val->unit_name,
                'follower'=>array(
                    'tw'=>1,
                    'growth_tw'=>$val->growth_tw,
                    'num_of_growth_tw'=>$val->num_of_growth_tw,
                    'tw_sekarang'=>$val->total_sekarang_tw,
                    'rank_tw'=>($rankTw[$val->total_sekarang_tw] + 1),
                    'fb'=>2,
                    'growth_fb'=>$val->growth_fb,
                    'num_of_growth_fb'=>$val->num_of_growth_fb,
                    'fb_sekarang'=>$val->total_sekarang_fb,
                    'rank_fb'=>($rankFb[$val->total_sekarang_fb] + 1),
                    'ig'=>3,
                    'growth_ig'=>$val->growth_ig,
                    'num_of_growth_ig'=>$val->num_of_growth_ig,
                    'ig_sekarang'=>$val->total_sekarang_ig,
                    'rank_ig'=>($rankIg[$val->total_sekarang_ig] + 1)
                )
            );
        }

        return $data;
    }

    public function higlight_program_account_all_tv(Request $request){
        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));

            if($request->has('pilih')){
                $pilih=$request->input('pilih');

                if($pilih=="on"){
                    if($request->has('kemarin')){
                        $kemarin=date('Y-m-d',strtotime($request->input('kemarin')));
                    }else{
                        $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
                    }
                }else{
                    $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
                }
            }else{
                $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
            }
        }else{
            $sekarang=date('Y-m-d');
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }

        $program=\DB::select("select b.id,e.unit_name, b.program_name,  
            sum(if(d.tanggal='$kemarin' and c.sosmed_id=1,d.follower,0)) as tw_kemarin,
            sum(if(d.tanggal='$sekarang' and c.sosmed_id=1,d.follower,0)) as tw_sekarang,
            ( 
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=1,d.follower,0)) 
                / 
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=1,d.follower,0))
                - 1
            ) * 100 as growth_tw,
            ( 
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=1,d.follower,0)) 
                - 
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=1,d.follower,0))
            ) as num_of_growth_tw,
            sum(if(d.tanggal='$kemarin' and c.sosmed_id=2,d.follower,0)) as fb_kemarin,
            sum(if(d.tanggal='$sekarang' and c.sosmed_id=2,d.follower,0)) as fb_sekarang,
            ( 
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=2,d.follower,0)) 
                / 
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=2,d.follower,0))
                - 1
            ) * 100 as growth_fb,
            ( 
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=2,d.follower,0)) 
                - 
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=2,d.follower,0))
            ) as num_of_growth_fb,
            sum(if(d.tanggal='$kemarin' and c.sosmed_id=3,d.follower,0)) as ig_kemarin,
            sum(if(d.tanggal='$sekarang' and c.sosmed_id=3,d.follower,0)) as ig_sekarang,
            ( 
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=3,d.follower,0)) 
                / 
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=3,d.follower,0))
                - 1
            ) * 100 as growth_ig,
            ( 
                sum(if(d.tanggal='$sekarang' and c.sosmed_id=3,d.follower,0)) 
                - 
                sum(if(d.tanggal='$kemarin' and c.sosmed_id=3,d.follower,0))
            ) as num_of_growth_ig
            from program_unit b
            left join unit_sosmed c on c.business_program_unit=b.id and c.type_sosmed='program'
            left join unit_sosmed_follower d on d.unit_sosmed_id=c.id and d.tanggal BETWEEN '$kemarin' and '$sekarang'
            left join business_unit as e on e.id=b.business_unit_id
            group by b.id");

        $arrTw=array();
        $arrFb=array();
        $arrIg=array();
        foreach($program as $k){
            array_push($arrTw,$k->tw_sekarang);
            array_push($arrFb,$k->fb_sekarang);
            array_push($arrIg,$k->ig_sekarang);
        }
        $rankTw=$arrTw;
        $rankFb=$arrFb;
        $rankIg=$arrIg;

        rsort($rankTw);
        rsort($rankFb);
        rsort($rankIg);

        $rankTw=array_flip($rankTw);
        $rankFb=array_flip($rankFb);
        $rankIg=array_flip($rankIg);

        $data=array();

        foreach($program as $val){
            $data[]=array(
                'id'=>$val->id,
                'unit_name'=>$val->program_name,
                'follower'=>array(
                    'tw'=>1,
                    'growth_tw'=>$val->growth_tw,
                    'num_of_growth_tw'=>$val->num_of_growth_tw,
                    'tw_sekarang'=>$val->tw_sekarang,
                    'rank_tw'=>($rankTw[$val->tw_sekarang] + 1),
                    'fb'=>2,
                    'growth_fb'=>$val->growth_fb,
                    'num_of_growth_fb'=>$val->num_of_growth_fb,
                    'fb_sekarang'=>$val->fb_sekarang,
                    'rank_fb'=>($rankFb[$val->fb_sekarang] + 1),
                    'ig'=>3,
                    'growth_ig'=>$val->growth_ig,
                    'num_of_growth_ig'=>$val->num_of_growth_ig,
                    'ig_sekarang'=>$val->ig_sekarang,
                    'rank_ig'=>($rankIg[$val->ig_sekarang] + 1)
                )
            );
        }

        return $data;
    }

    public function highlight_target_achivement(Request $request){
        if($request->has('tanggal')){
            $sekarang=date('Y-m-d',strtotime($request->input('tanggal')));

            if($request->has('pilih')){
                $pilih=$request->input('pilih');

                if($pilih=="on"){
                    if($request->has('kemarin')){
                        $kemarin=date('Y-m-d',strtotime($request->input('kemarin')));
                    }else{
                        $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
                    }
                }else{
                    $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
                }
            }else{
                $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
            }
        }else{
            $sekarang=date('Y-m-d');
            $kemarin = date('Y-m-d', strtotime('-1 day', strtotime($sekarang)));
        }

        if($request->has('group')){
            $group=$request->input('group');
        }else{
            $group=1;
        }

        $target=\DB::select("select a.id, a.group_unit_id, a.unit_name, b.target_use, 
            sum(if(b.sosmed_id=1, c.target,0)) as target_tw,
            sum(if(d.tanggal='$sekarang' and b.sosmed_id=1, d.follower,0)) as follower_tw,
            ( sum(if(d.tanggal='$sekarang' and b.sosmed_id=1, d.follower,0)) / sum(if(b.sosmed_id=1, c.target,0)) * 100) as acv_tw,
            sum(if(b.sosmed_id=2, c.target,0)) as target_fb,
            sum(if(d.tanggal='$sekarang' and b.sosmed_id=2, d.follower,0)) as follower_fb,
            ( sum(if(d.tanggal='$sekarang' and b.sosmed_id=2, d.follower,0)) / sum(if(b.sosmed_id=2, c.target,0)) * 100) as acv_fb,
            sum(if(b.sosmed_id=3, c.target,0)) as target_ig,
            sum(if(d.tanggal='$sekarang' and b.sosmed_id=3, d.follower,0)) as follower_ig,
            ( sum(if(d.tanggal='$sekarang' and b.sosmed_id=3, d.follower,0)) / sum(if(b.sosmed_id=3, c.target,0)) * 100) as acv_ig
            from business_unit a
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='corporate'
            left join unit_sosmed_target c on c.unit_sosmed_id=b.id
            left join unit_sosmed_follower d on d.unit_sosmed_id=b.id and d.tanggal='$sekarang'
            where a.group_unit_id='$group'
            GROUP by a.id");

        $tambahanInews=\DB::select("select ifnull(a.id,'TOTAL') as id, a.business_unit_id,
            d.group_unit_id, a.program_name,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) as tw_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) as tw_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=1, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=1, c.follower,0)) -1)*100 as growth_tw,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) as fb_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) as fb_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=2, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=2, c.follower,0)) -1)*100 as growth_fb,
            sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) as ig_kemarin,
            sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) as ig_sekarang,
            (sum(if(c.tanggal='$sekarang' and b.sosmed_id=3, c.follower,0)) / sum(if(c.tanggal='$kemarin' and b.sosmed_id=3, c.follower,0)) -1)*100 as growth_ig
            from program_unit a 
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id and c.tanggal BETWEEN '$kemarin' and '$sekarang'
            left join business_unit d on d.id=a.business_unit_id
            where a.id in (89, 101, 95, 87)
            group by a.id
            with ROLLUP");

        $data=array();
        foreach($target as $row){
            if($row->id==4){
                foreach($tambahanInews as $in){
                    if($in->id=="TOTAL"){
                        $data[]=array(
                            'id'=>$row->id,
                            'unit_name'=>$row->unit_name,
                            'follower'=>array(
                                'tw'=>1,
                                'target_tw'=>$row->target_tw,
                                'tw_sekarang'=>($row->follower_tw+$in->tw_sekarang),
                                'acv_tw'=>number_format(($row->follower_tw+$in->tw_sekarang) /  $row->target_tw * 100),
                                'fb'=>2,
                                'target_fb'=>$row->target_fb,
                                'fb_sekarang'=>($row->follower_fb+$in->fb_sekarang),
                                'acv_fb'=>number_format(($row->follower_fb+$in->fb_sekarang) /  $row->target_fb * 100),
                                'ig'=>3,
                                'target_ig'=>$row->target_ig,
                                'ig_sekarang'=>($row->follower_ig+$in->ig_sekarang),
                                'acv_ig'=>number_format(($row->follower_ig+$in->ig_sekarang) /  $row->target_ig * 100)
                            )
                        );
                    }
                }
            }else{
                $data[]=array(
                    'id'=>$row->id,
                    'unit_name'=>$row->unit_name,
                    'follower'=>array(
                        'tw'=>1,
                        'target_tw'=>$row->target_tw,
                        'tw_sekarang'=>$row->follower_tw,
                        'acv_tw'=>number_format($row->follower_tw /  $row->target_tw * 100),
                        'fb'=>2,
                        'target_fb'=>$row->target_fb,
                        'fb_sekarang'=>$row->follower_fb,
                        'acv_fb'=>number_format($row->follower_fb /  $row->target_fb * 100),
                        'ig'=>3,
                        'target_ig'=>$row->target_ig,
                        'ig_sekarang'=>$row->follower_ig,
                        'acv_ig'=>number_format($row->follower_ig /  $row->target_ig * 100)
                    )
                );
            }
        }

        return $data;
    }

    public function daily_chart_program(Request $request,$id){
        if($request->has('bulan')){
            $bulan=date('Y-m',strtotime($request->input('bulan')));
        }else{
            $bulan=date('Y-m');
        }

        $program=\DB::select("select a.id, a.business_unit_id, a.program_name, c.tanggal, 
            sum(if(b.sosmed_id=1, c.follower,0)) as tw,
            sum(if(b.sosmed_id=2, c.follower,0)) as fb,
            sum(if(b.sosmed_id=3, c.follower,0)) as ig
            from program_unit a 
            left join unit_sosmed b on b.business_program_unit=a.id and b.type_sosmed='program'
            left join unit_sosmed_follower c on c.unit_sosmed_id=b.id
            where a.id=$id
            and date_format(c.tanggal,'%Y-%m')='$bulan'
            group by c.tanggal");

        $tanggal=array();
        $fb=array();
        $ig=array();
        $tw=array();
        foreach($program as $row){
            array_push($tanggal,date('d',strtotime($row->tanggal)));
            array_push($tw,$row->tw);
            array_push($fb,$row->fb);
            array_push($ig,$row->ig);
        }

        return array(
            'tanggal'=>$tanggal,
            'tw'=>$tw,
            'fb'=>$fb,
            'ig'=>$ig
        );
    }
}