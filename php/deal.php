<?php
class deal extends group
{
    private $advertiser_id; // id рекламодателя
    private $doer_id; // id исполнителя
    private $planpost_id; // id запланированного поста
    private $doer_grp_id; // id группы вк исполнителя
    private $advertiser_grp_id; // id группы вк рекламодателя 
    private $vk_post_id; // id поста в вк
    private $unix_date_del; // время удаления в unix
    private $unix_date; // время старта в unix
    private $date; // день публикации поста
    private $time; // время публикации поста
    private $token; // токен исполнителя
    private $doer_grp_freezing; // сколько денег замораживает группа исполнителя
    private $doer_balance; // баланс исполнителя 
    private $advertiser_balance; // баланс рекламодателя
    private $subs; // подписки
    private $reach; // охват
    private $price; // цена подписчика без комиссии
    private $start_data; // дан ли старт сделке
    private $status; // статус
    private $ref_user; // id реферера

    function __construct(int $planpost_id)
    {
        $this->planpost_id = $planpost_id;
        $check = false;
        if (R::count('planpost', 'id = ?', array($planpost_id)) == 1) {
            $planpost = R::findOne('planpost', 'id = ?', array($planpost_id));
            $check = true;
        }
        if ($check) {
            $this->advertiser_id = $planpost->rekl_id;
            $this->doer_id = $planpost->adm_id;
            $this->vk_post_id = $planpost->post_id;
            $this->advertiser_grp_id = $planpost->rekl_grp_id;
            $this->doer_grp_id = $planpost->adm_grp_id;
            $this->unix_date_del = $planpost->unix_date_del;
            $this->unix_date = $planpost->unix_date;
            $this->date = $planpost->data;
            $this->time = $planpost->time;
            $this->token = $planpost->token;
            $this->advertiser_expenses = $planpost->param26;
            $this->price = $planpost->price_rekl;
            $this->start_data = $planpost->start_data;
            $this->status = $planpost->status;
            $this->doer_grp_freezing = $planpost->frozen_subs;
            if (R::count('users', 'id = ?', array($planpost->rekl_id))) {
                $rekl_user = R::findOne('users', 'id = ?', array($planpost->rekl_id));
                $this->advertiser_balance = $rekl_user->balance;
            } else {
                throw new Exception('Advertiser group not found.');
                if (R::count('users', 'id = ?', array($planpost->adm_id))) {
                    $doer_user = R::findOne('users', 'id = ?', array($planpost->rekl_id));
                    $this->doer_balance =  $doer_user->balance;
                } else {
                    throw new Exception('Doer not found.');
                }
            }
        } else {
            throw new Exception('Deal not found.');
        }
    }

    public function getAdvertiser()
    {
        return $this->advertiser_id;
    }

    public function getDoer()
    {
        return $this->doer_id;
    }

    public function getPlanpost()
    {
        return $this->planpost_id;
    }

    public static function createDeal($publdate, int $term, int $doer_id, int $advertiser_id, int $doer_group_id, int $advertiser_group_id, int $vk_post_id)
    {
        $advertiser_group = R::findOne('groups', 'group_id = ? and user_id = ?', array($advertiser_group_id, $advertiser_id));
        $doer_group = R::findOne('groups', 'group_id = ? and user_id = ?', array($doer_group_id, $doer_id));
        $advertiser = new user($advertiser_id);
        if ($advertiser->getBalance() > $doer_group->frozen_money) {
            $date_and_time = explode(' ', $publdate);
            $unix_det_date = strtotime($publdate);
            $planpost = R::dispense('planpost');
            $user = R::findOne('users', 'id = ?', array($doer_id));
            $planpost->login = $user->login;
            $planpost->user_id = $user->id;
            $planpost->adm_grp_id = $doer_group_id;
            $planpost->rekl_grp_id = $advertiser_group_id;
            $planpost->adm_id = $user->id;
            $planpost->rekl_id = $advertiser_id;
            $planpost->adm_grp_name = $doer_group->name;
            $planpost->rekl_grp_name = $advertiser_group->name;
            $planpost->post_id = $vk_post_id + 1;
            $planpost->data = $date_and_time[0];
            $planpost->time = $date_and_time[1];
            $planpost->frozen_subs = $doer_group->frozen_money;
            $post_price_db = R::findOne('advert', 'group_id = ? and user_id = ?', array($advertiser_group_id, $advertiser_id));
            $planpost->price_rekl = $post_price_db->price;
            $planpost->price = (($post_price_db->price * (100 - COMMISSION)) / 100);
            $planpost->advert_id = $post_price_db->id;
            $planpost->param18 = 0;
            $planpost->param19 = 0;
            $planpost->param20 = 0;
            $planpost->param22 = 0;
            $planpost->param26 = 0;
            $planpost->param27 = 0;
            $planpost->status = 3;
            $planpost->unix_date = $unix_det_date;
            $planpost->unix_date_del = $unix_det_date + ($term * 3600);
            $planpost->error_message = NULL;
            $planpost->token = $doer_group->token;
            $planpost->start_data = 0;

            do {
                $unique_id = md5(time() . rand(100000, 999999) . $advertiser_id . $advertiser_group->name . $doer_group->token . 'rivre74wdwVTR67F56Vgby');
            } while (R::count('planpost', "unique_id = ?", array($unique_id)) > 0);
            $planpost->unique_id = $unique_id;
            if (R::store($planpost)) {
                $planpost = R::findOne('planpost', 'unique_id = ?', array($unique_id));
                return $planpost->id;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function changeStatus()
    {
        if ($this->unix_date <= time()) {
            $planpost = R::findOne('planpost', 'id = ?', array($this->planpost_id));
            $planpost->status = 1;
            $this->status = 1;
            if (R::store($planpost)) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function calcFreezingMoney()
    {
        $freezing_money_count = $this->doer_grp_freezing * $this->price;
        $freezing_money_count = bcdiv($freezing_money_count, 1, 2);
        $freezing_money_count = rtrim(rtrim($freezing_money_count, '0'), '.');
        return $freezing_money_count;
    }

    public function freezingAdvertiserBalance()
    {
        if ($this->advertiser_balance > $this->calcFreezingMoney()) {
            $advertiser = R::findOne('users', 'id = ?', array($this->advertiser_id));
            $change_balance = $this->advertiser_balance - $this->calcFreezingMoney();
            $advertiser->frozen_balance += $this->calcFreezingMoney();
            $advertiser->balance = $change_balance;
            if (R::store($advertiser)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function update()
    {
        $owner_id = $this->doer_grp_id;
        $post_id = $this->vk_post_id;
        $post_stat = json_decode(file_get_contents('https://api.vk.com/method/stats.getPostReach?&access_token=' . ADMINTOKEN . '&owner_id=-' . $owner_id . '&post_ids=' . $post_id . '&v=' . VERSION), true);
        $post_stat2 = json_decode(file_get_contents('https://api.vk.com/method/wall.getById?&access_token=' . ADMINTOKEN . '&posts=-' . $owner_id . '_' . $post_id . '&v=' . VERSION), true);
        $planpost = R::findOne('planpost', 'id = ?', array($this->planpost_id));
        if (!isset($post_stat2['response'][0]['id'])) {
            $planpost->status = 0;
            $planpost->error_message = 'Пост был удалён. Новые подписчики засчитаны. Средства списаны со счёта рекламодателя и зачислены на счёт исполнителя. Сделка завершена.';
            $this->finish();
        }
        $planpost->param22 = $post_stat["response"][0]['reach_total']; // охват
        $this->reach = $post_stat["response"][0]['reach_total'];
        if (R::store($planpost)) {
            return true;
        } else {
            return false;
        }
    }

    public function checkToken()
    {
        $checkToken = json_decode(file_get_contents('https://api.vk.com/method/users.get?&access_token=' . $this->token . '&fields=uid,first_name,last_name&v=' . VERSION), true);
        if (isset($checkToken["error"])) {
            if ($checkToken["error"]["error_code"] == 5) {
                $planpost = R::findOne('planpost', 'id = ?', array($this->planpost_id));
                $planpost->status = 0;
                R::store($planpost);
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    public function percentBanUsers()
    {
        $BanUsersCount = 0;
        $adv_id = $this->planpost_id;
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/promoevents/' . $adv_id . '/' . 'totalSubscribers.txt')) {
            $usersArray = readFileMembers('totalSubscribers.txt', $adv_id);
            foreach ($usersArray as $subs) {
                $checkToken = json_decode(file_get_contents('https://api.vk.com/method/users.get?&access_token=' . ADMINTOKEN . '&user_ids=' . $subs['id'] . '&fields=uid&v=' . VERSION), true);
                if (isset($checkToken["response"][0]["deactivated"])) {
                    $BanUsersCount++;
                }
            }
            $botsPercent = (($BanUsersCount * 100) / count($usersArray));
            $botsPercent = round($botsPercent);
            return $botsPercent;
        } else {
            return false;
        }
    }

    public function processingBanGroup()
    {
        if ($this->percentBanUsers() > MAX_BOT_PERCENT) {
            $planpost = R::findOne('planpost', 'id = ?', array($this->planpost_id));
            $planpost->error_message = 'Подозрение на накрутку. Деньги возвращены рекламодателю. Группа исполнителя заблокирована.';
            if (R::store($planpost)) {
                $this->forcedFinish();
                $group = R::findOne('groups', 'group_id = ?', array($this->doer_grp_id));
                $ban = new group($group->id, $this->doer_id);
                $ban->banGroup();
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getDoerGroupMembers()
    {
        $admGroupMembers = array();
        $page = 0;
        $member = 0;
        $limit = 1000;
        $users = array();
        $adv_id = $this->planpost_id;
        $admGroupId = $this->doer_grp_id;
        $admToken = $this->token;
        $group_id = $admGroupId;
        $token = $admToken;
        if ($this->checkToken()) {
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/promoevents/' . $adv_id)) {
                do {
                    $offset = $page * $limit;
                    $members = json_decode(file_get_contents("https://api.vk.com/method/groups.getMembers?group_id=" . $group_id . "&v=5.16&offset=" . $offset . "&count=" . $limit . "&fields=sex,bdate,city,country,photo_200_orig,photo_max_orig&access_token=" . $token . "&v=" . VERSION), true);
                    usleep(334000);
                    foreach ($members['response']['items'] as $user) {
                        $users[] = $user;
                        $member++;
                    }
                    $page++;
                } while ($members['response']['count'] > $offset + $limit);

                // foreach ($users as $n => $user)
                //     if (@$user['deactivated'])
                //         unset($users[$n]);
                for ($i = 0; $i < count($users); $i++) {
                    if (isset($users[$i])) {
                        $admGroupMembers[] = $users[$i]['id'];
                    }
                }
                makeFileMembers($admGroupMembers, 'admGroupMembers.txt', $adv_id);
                return true;
            } else {
                return false;
            }
        } else {
            $planpost = R::findOne('planpost', 'id = ?', array($this->planpost_id));
            $planpost->status = 0;
            $planpost->error_message = "У сервиса пропал доступ к группе исполнителя. Новые подписчики засчитаны. Средства списаны со счёта рекламодателя и зачислены на счёт исполнителя. Сделка завершена.";
            R::store($planpost);
            $this->finish();
            return false;
        }
    }

    public function getGeneralGroupMembers()
    {
        $adv_id = $this->planpost_id;
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/promoevents/' . $adv_id)) {
            $reklGroupMembers = array();
            $generalGroupMembers = array();
            $page = 0;
            $member = 0;
            $limit = 1000;
            $users_rekl = array();
            $reklGroupId = $this->advertiser_grp_id;
            $page = 0;
            $member = 0;
            $group_id = $reklGroupId;
            $token = ADMINTOKEN;
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/promoevents/' . $adv_id . '/admGroupMembers.txt')) {
                $admGroupMembers = readFileMembers('admGroupMembers.txt', $adv_id);
                do {
                    $offset = $page * $limit;
                    $members_rekl = json_decode(file_get_contents("https://api.vk.com/method/groups.getMembers?group_id=" . $group_id . "&v=5.16&offset=" . $offset . "&count=" . $limit . "&fields=sex,bdate,city,country,photo_200_orig,photo_max_orig&access_token=" . $token . "&v=" . VERSION), true);
                    usleep(334000);
                    foreach ($members_rekl['response']['items'] as $user) {
                        $users_rekl[] = $user;
                        $member++;
                    }
                    $page++;
                } while ($members_rekl['response']['count'] > $offset + $limit);

                // foreach ($users_rekl as $n => $user)
                //     if (@$user['deactivated'])
                //         unset($users_rekl[$n]);
                for ($i = 0; $i < count($users_rekl); $i++) {
                    if (isset($users_rekl[$i])) {
                        $reklGroupMembers[] = $users_rekl[$i]['id'];
                    }
                }
                $generalGroupMembers = array_intersect($admGroupMembers, $reklGroupMembers);
                makeFileMembers($generalGroupMembers, 'generalGroupMembers.txt', $adv_id);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function startCalcSubs()
    {
        $adv_id = $this->planpost_id;
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/promoevents/' . $adv_id)) {
            mkdir($_SERVER['DOCUMENT_ROOT'] . '/promoevents/' . $adv_id, 0777);
        }
        $this->getDoerGroupMembers();
        $this->getGeneralGroupMembers();
        $this->start_data = 1;
        $planpost = R::findOne('planpost', 'id = ?', array($this->planpost_id));
        $planpost->start_data = 1;
        if (R::store($planpost)) {
            return true;
        } else {
            return false;
        }
    }

    public function calcSubs()
    {
        $adv_id = $this->planpost_id;
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/promoevents/' . $adv_id . '/admGroupMembers.txt') && file_exists($_SERVER['DOCUMENT_ROOT'] . '/promoevents/' . $adv_id . '/generalGroupMembers.txt')) {
            $admGroupMembers = array();
            $reklGroupMembers = array();
            $generalGroupMembers = array();
            $totalSubscribersFull = array();
            $page = 0;
            $member = 0;
            $limit = 1000;
            $users_rekl = array();
            if ($this->start_data > 0) {
                $reklGroupId = $this->advertiser_grp_id;
                $admGroupMembers = readFileMembers('admGroupMembers.txt', $adv_id);
                $generalGroupMembers = readFileMembers('generalGroupMembers.txt', $adv_id);
                // rekl group members //
                $page = 0;
                $member = 0;
                $group_id = $reklGroupId;
                $token = ADMINTOKEN;
                do {
                    $offset = $page * $limit;
                    $members_rekl = json_decode(file_get_contents("https://api.vk.com/method/groups.getMembers?group_id=" . $group_id . "&v=5.16&offset=" . $offset . "&count=" . $limit . "&fields=sex,bdate,city,country,photo_200_orig,photo_max_orig&access_token=" . $token . "&v=" . VERSION), true);
                    usleep(334000);
                    foreach ($members_rekl['response']['items'] as $user) {
                        $users_rekl[] = $user;
                        $member++;
                    }
                    $page++;
                } while ($members_rekl['response']['count'] > $offset + $limit);
                // foreach ($users_rekl as $n => $user)
                //     if (@$user['deactivated'])
                //         unset($users_rekl[$n]);
                for ($i = 0; $i < count($users_rekl); $i++) {
                    if (isset($users_rekl[$i])) {
                        $reklGroupMembers[] = $users_rekl[$i]['id'];
                        $reklGroupMembersFull[] = $users_rekl[$i];
                    }
                }
                $advertGroupMembers = array_intersect($admGroupMembers, $reklGroupMembers);
                $totalSubscribers = array_diff($advertGroupMembers, $generalGroupMembers);
                $totalSubscribersCount = count($totalSubscribers);
                for ($i = 0; $i < count($reklGroupMembersFull); $i++) {
                    if (in_array($reklGroupMembersFull[$i]['id'], $totalSubscribers)) {
                        $totalSubscribersFull[] = $reklGroupMembersFull[$i];
                    }
                }
                makeFileMembers($totalSubscribersFull, 'totalSubscribers.txt', $adv_id);
                $update_stat = R::findOne('planpost', 'id = ?', array($adv_id));
                $max = $this->calcMaxSubs();
                if ($totalSubscribersCount >= $max) {
                    $price = $update_stat->price_rekl;
                    $totalProfit = $price * $max;
                    $update_stat->param18 = $max;
                    $update_stat->profit = $totalProfit;
                    R::store($update_stat);
                } else {
                    $price = $update_stat->price_rekl;
                    $totalProfit = $price * $totalSubscribersCount;
                    $update_stat->param18 = $totalSubscribersCount;
                    $update_stat->profit = $totalProfit;
                    R::store($update_stat);
                }
                if ($totalSubscribersCount > 0) {
                    $advert_stat = R::findOne('advert', 'group_id = ?', array($reklGroupId));
                    $advert_stat->subscriptions += $totalSubscribersCount;
                    R::store($advert_stat);
                }
                return $totalSubscribersCount;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function calcMaxSubs()
    {
        return $this->doer_grp_freezing;
    }

    public function advertiserIsReferral()
    {
        $advertiser = R::findOne('users', 'id = ?', array($this->advertiser_id));
        if (isset($advertiser->ref_user)) {
            return $advertiser->ref_user;
        } else {
            return false;
        }
    }

    public function doerIsReferral()
    {
        $doer = R::findOne('users', 'id = ?', array($this->doer_id));
        if (isset($doer->ref_user)) {
            return $doer->ref_user;
        } else {
            return false;
        }
    }

    public function calcAdvertiserReferrerProfit()
    {
        $AdvertiserReferrerProfit = $this->calcAdpressProfit() * (REFERRER_COMMISSION / 100);
        $AdvertiserReferrerProfit = round($AdvertiserReferrerProfit, 2);
        return $AdvertiserReferrerProfit;
    }

    public function calcDoerReferrerProfit()
    {
        $DoerReferrerProfit = $this->calcAdpressProfit() * (REFERRER_COMMISSION / 100);
        $DoerReferrerProfit = round($DoerReferrerProfit, 2);
        return $DoerReferrerProfit;
    }

    public function updateAdvertiserReferrerBalance()
    {
        $advertiserIsReferral = $this->advertiserIsReferral();
        if ($advertiserIsReferral) {
            $AdvertiserReferrer = R::findOne('users', 'id = ?', array($advertiserIsReferral));
            $AdvertiserReferrerProfit = $this->calcAdvertiserReferrerProfit();
            $AdvertiserReferrer->balance += $AdvertiserReferrerProfit;
            if (R::store($AdvertiserReferrer)) {
                transaction::create(5, 1, $AdvertiserReferrerProfit, $advertiserIsReferral);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function updateDoerReferrerBalance()
    {
        $doerIsReferral = $this->doerIsReferral();
        if ($doerIsReferral) {
            $DoerReferrer = R::findOne('users', 'id = ?', array($doerIsReferral));
            $DoerReferrerProfit = $this->calcDoerReferrerProfit();
            $DoerReferrer->balance += $DoerReferrerProfit;
            if (R::store($DoerReferrer)) {
                transaction::create(5, 1, $DoerReferrerProfit, $doerIsReferral);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function calcAdpressProfit()
    {
        $AdpressProfit = $this->calcAdvertiserExpenses() * ((COMMISSION) / 100);
        $AdpressProfit = round($AdpressProfit, 2);
        return $AdpressProfit;
    }

    public function calcCleanAdpressProfit()
    {
        $CleanAdpressProfit = $this->calcAdpressProfit();
        if ($this->doerIsReferral()) {
            $CleanAdpressProfit -= $this->calcDoerReferrerProfit();
        }
        if ($this->advertiserIsReferral()) {
            $CleanAdpressProfit -= $this->calcDoerReferrerProfit();
        }
        $CleanAdpressProfit = round($CleanAdpressProfit, 2);
        return $CleanAdpressProfit;
    }

    public function calcAdvertiserExpenses()
    {
        $AdvertiserExpenses = $this->calcSubs() * $this->price;
        $AdvertiserExpenses = round($AdvertiserExpenses, 2);
        return $AdvertiserExpenses;
    }

    public function calcDoerProfit()
    {
        $DoerProfit = $this->calcAdvertiserExpenses() * ((100 - COMMISSION) / 100);
        $DoerProfit = round($DoerProfit, 2);
        return $DoerProfit;
    }

    public function checkMoney()
    {
        if ($this->calcSubs() >= $this->calcMaxSubs()) {
            return false;
        } else {
            return true;
        }
    }


    public function updateAdvertiserBalance()
    {
        if ($this->checkMoney()) {
            $advertiser = R::findOne('users', 'id = ?', array($this->advertiser_id));
            $AdvertiserExpenses = $this->calcAdvertiserExpenses();
            $remains = $this->calcFreezingMoney() - $AdvertiserExpenses; // заморозка - затраты рекла
            if ($remains > 0) {
                $rekl_new_balance = $this->advertiser_balance + $remains; // баланс рекла + остаток заморозки
                $advertiser->balance = $rekl_new_balance; // запись нового баланса в бд
            }
            $advertiser->frozen_balance -= $this->calcFreezingMoney(); // списание заморозки
            if (R::store($advertiser)) {
                transaction::create(4, 1, $AdvertiserExpenses, $this->advertiser_id);
                return true;
            } else {
                return false;
            }
        } else {
            $advertiser = R::findOne('users', 'id = ?', array($this->advertiser_id));
            $advertiser->frozen_balance -= $this->calcFreezingMoney(); // списание заморозки
            if (R::store($advertiser)) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function updateDoerBalance()
    {
        if ($this->checkMoney()) {
            $doer = R::findOne('users', 'id = ?', array($this->doer_id));
            $DoerProfit = $this->calcDoerProfit();
            $contractor_ptofit = $DoerProfit; // прибыль исполнителя
            if ($contractor_ptofit > 0) {
                $doer->balance += $contractor_ptofit; // запись нового баланса к бд
            }
            if (R::store($doer)) {
                transaction::create(3, 1, $DoerProfit, $this->doer_id);
                return true;
            } else {
                return false;
            }
        } else {
            $doer = R::findOne('users', 'id = ?', array($this->doer_id));
            $profit = $this->calcFreezingMoney() * ((100 - COMMISSION) / 100); // прибавка прибыли к балансу
            $doer->balance += $profit; // запись нового баланса к бд
            if (R::store($doer)) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function refund()
    {
        $advertiser = R::findOne('users', 'id = ?', array($this->advertiser_id));
        $advertiser->frozen_balance -= $this->calcFreezingMoney();
        $advertiser->balance += $this->calcFreezingMoney();
        if (R::store($advertiser)) {
            return true;
        } else {
            return false;
        }
    }

    public function deteleVkPost()
    {
        $vk_del = json_decode(file_get_contents('https://api.vk.com/method/wall.delete?&access_token=' . $this->token . '&owner_id=-' . $this->doer_grp_id . '&post_id=' . $this->vk_post_id . '&v=' . VERSION), true);
        if ($vk_del['response'] == 1 || $vk_del) {
            return true;
        } else {
            return false;
        }
        // поработать с ошибками от вк
    }

    public function deteleDoerGroupMembersFile()
    {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/promoevents/' . $this->planpost_id . '/admGroupMembers.txt')) {
            unlink($_SERVER['DOCUMENT_ROOT'] . '/promoevents/' . $this->planpost_id . '/admGroupMembers.txt');
            return true;
        } else {
            return false;
        }
    }

    public function deteleGeneralGroupMembersFile()
    {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/promoevents/' . $this->planpost_id . '/generalGroupMembers.txt')) {
            unlink($_SERVER['DOCUMENT_ROOT'] . '/promoevents/' . $this->planpost_id . '/generalGroupMembers.txt');
            return true;
        } else {
            return false;
        }
    }

    public function writeMessage(string $message)
    {
        $planpost = R::findOne('planpost', 'id = ?', array($this->planpost_id));
        $planpost->error_message = $message;
        R::store($planpost);
    }

    public function finish()
    {
        if ($this->deteleVkPost()) {
            if ($this->updateAdvertiserBalance() && $this->updateDoerBalance()) {
                $this->updateDoerReferrerBalance();
                $this->updateAdvertiserReferrerBalance();
                AP::profit($this->calcCleanAdpressProfit(), 'iamkT222O029292ggYMf');
                $this->deteleDoerGroupMembersFile();
                $this->deteleGeneralGroupMembersFile();
                $planpost = R::findOne('planpost', 'id = ?', array($this->planpost_id));
                $planpost->status = 2;
                if (R::store($planpost)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                $planpost = R::findOne('planpost', 'id = ?', array($this->planpost_id));
                $planpost->error_message = "Ошибка. Серверу не удалось обновить балансы пользователей. Обратитесь в поддержку!";
                $planpost->status = 0;
                R::store($planpost);
                return false;
            }
        } else {
            return false;
        }
    }

    public function forcedFinish()
    {
        if ($this->deteleVkPost()) {
            $this->refund();
            $this->deteleDoerGroupMembersFile();
            $this->deteleGeneralGroupMembersFile();
            $planpost = R::findOne('planpost', 'id = ?', array($this->planpost_id));
            $planpost->status = 0;
            if (R::store($planpost)) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function deleteTotalSubscribers()
    {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/promoevents/' . $this->planpost_id . '/totalSubscribers.txt')) {
            unlink($_SERVER['DOCUMENT_ROOT'] . '/promoevents/' . $this->planpost_id . '/totalSubscribers.txt');
            return true;
        } else {
            return true;
        }
    }

    public function deleteDirectory()
    {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/promoevents/' . $this->planpost_id)) {
            unlink($_SERVER['DOCUMENT_ROOT'] . '/promoevents/' . $this->planpost_id);
        }
    }

    public function finallyDelete()
    {
        $delTotalSubs = $this->unix_date_del + DEL_TOTAL_SUBS;
        if ($delTotalSubs <= time()) {
            $this->deleteTotalSubscribers();
        }
    }
}
