$(document).ready(function(){
var province ="安徽-34$北京-11$福建-35$甘肃-62$广东-44$广西-45$贵州-52$海南-46$河北-13$河南-41$黑龙江-23$湖北-42$湖南-43$吉林-22$江苏-32$江西-36$辽宁-21$内蒙-15$宁夏-64$青海-63$山东-37$山西-14$陕西-61$上海-31$四川-51$台湾-71$天津-12$西藏-54$新疆-65$云南-53$浙江-33$重庆-55";
    var city = "15-阿尔山市-1986$65-阿拉尔市-9031$21-鞍山市-2230$23-安达市-2762$13-安国市-1369$42-安陆市-5353$34-安庆市-3680$37-安丘市-4581$41-安阳市-4960$13-霸州市-1467$22-白城市-2470$22-白山市-2460$62-白银市-8240$34-蚌埠市-3630$15-包头市-1920$13-保定市-1340$61-宝鸡市-7930$23-北安市-2782$45-北海市-6230$11-北京市-1000$45-北流市-6246$21-北宁市-2273$21-北票市-2346$21-本溪市-2250$13-泊头市-1441$13-沧州市-1431$13-沧州市-1430$37-昌邑市-4585$43-常德市-5580$32-常熟市-3055$32-常州市-3040$22-长春市-2410$41-长葛市-5031$43-长沙市-5510$14-长治市-1640$21-朝阳市-2340$44-潮阳市-5864$44-潮州市-5869$43-郴州市-5630$51-成都市-6510$44-澄海市-5861$13-承德市-1410$34-池州市-3790$42-赤壁市-5362$15-赤峰市-1940$52-赤水市-7043$45-崇左市-6128$34-滁州市-3750$33-慈溪市-3325$22-大安市-2474$32-大丰市-3116$21-大连市-2220$23-大庆市-2650$21-大石桥市-2262$14-大同市-1620$42-大冶市-5221$21-丹东市-2260$42-丹江口市-5391$32-丹阳市-3141$42-当阳市-5262$63-德令哈市-8592$36-德兴市-4342$51-德阳市-6580$37-德州市-4680$21-灯塔市-2312$41-邓州市-5132$41-登封市-5136$13-定州市-1351$21-东港市-2263$32-东台市-3117$45-东兴市-6331$33-东阳市-3388$37-东营市-4550$44-东莞市-6020$22-敦化市-2493$62-敦煌市-8263$51-峨嵋山市-6664$15-额尔古纳市-1967$42-鄂州市-5310$44-恩平市-5893$15-二连浩特市-2011$45-防城港市-6330$37-肥城市-4632$14-汾阳市-1731$36-丰城市-4312$15-丰镇市-2039$33-奉化市-3326$21-凤城市-2261$44-佛山市-5880$35-福安市-4034$35-福鼎市-4032$52-福泉市-7154$35-福州市-3910$21-抚顺市-2240$36-抚州市-4370$65-阜康市-8856$21-阜新市-2290$34-阜阳市-3720$23-富锦市-2731$21-盖州市-2282$36-赣州市-4280$36-高安市-4314$37-高密市-4586$14-高平市-1683$44-高要市-5931$32-高邮市-3121$44-高州市-5922$63-格尔木市-8591$15-根河市-1968$22-公主岭市-2434$51-广安市-6737$51-广汉市-6584$42-广水市-5354$51-广元市-6610$44-广州市-5810$45-桂林市-6170$45-桂平市-6243$45-贵港市-6242$36-贵溪市-4271$52-贵阳市-7010$23-哈尔滨市-2610$21-海城市-2232$46-海口市-6410$23-海林市-2752$23-海伦市-2764$32-海门市-3065$33-海宁市-3355$37-海阳市-4564$13-邯郸市-1270$13-邯郸市-1271$61-韩城市-7972$42-汉川市-5357$61-汉中市-7990$33-杭州市-3310$22-和龙市-2496$34-合肥市-3610$45-合山市-6151$45-河池市-6281$14-河津市-1818$44-河源市-5980$41-鹤壁市-4970$23-鹤岗市-2670$44-鹤山市-5895$45-贺州市-6225$23-黑河市-2780$13-衡水市-1480$43-衡阳市-5540$42-洪湖市-5373$43-洪江市-5672$14-侯马市-1772$15-呼和浩特市-1910$21-葫芦岛市-2276$33-湖州市-3360$23-虎林市-2756$61-华阴市-7974$51-华蓥市-6732$44-化州市-5924$43-怀化市-5670$32-淮安市-3080$34-淮北市-3660$34-淮南市-3640$42-黄冈市-5330$34-黄山市-3710$42-黄石市-5220$13-黄骅市-1451$41-辉县市-4988$44-惠州市-5950$15-霍林郭勒市-1992$14-霍州市-1777$23-鸡西市-2660$36-吉安市-4350$22-吉林市-2420$22-集安市-2455$37-即墨市-4521$13-冀州市-1482$37-济南市-4510$37-济宁市-4610$33-嘉兴市-3350$62-嘉峪关市-8220$23-佳木斯市-2690$51-简阳市-6637$35-建阳市-4014$35-建瓯市-4015$32-姜堰市-3132$32-江都市-3125$44-江门市-5890$33-江山市-3415$32-江阴市-3022$51-江油市-6597$41-焦作市-5010$37-胶南市-4522$37-胶州市-4525$44-揭阳市-5865$34-界首市-3731$14-介休市-1761$62-金昌市-8230$33-金华市-3380$32-金坛市-3042$43-津市市-5587$21-锦州市-2270$14-晋城市-1680$35-晋江市-3972$14-晋中市-1750$42-荆门市-5320$42-荆州市-5370$36-景德镇市-4220$32-靖江市-3123$36-九江市-4240$62-酒泉市-8266$32-句容市-3142$41-开封市-4920$44-开平市-5894$21-开原市-2337$53-开远市-7432$65-奎屯市-8981$53-昆明市-7310$32-昆山市-3052$54-拉萨市-7700$37-莱芜市-4634$37-莱西市-4523$37-莱阳市-4568$37-莱州市-4569$33-兰溪市-3386$62-兰州市-8210$13-廊坊市-1460$42-老河口市-5287$44-乐昌市-5825$37-乐陵市-4682$36-乐平市-4221$33-乐清市-3333$51-乐山市-6650$44-雷州市-5914$43-冷水江市-5622$33-丽水市-3430$32-连云港市-3070$44-连州市-6014$44-廉江市-5912$43-涟源市-5623$37-聊城市-4710$21-辽阳市-2310$22-辽源市-2440$41-林州市-4961$14-临汾市-1770$33-临海市-3452$22-临江市-2422$37-临清市-4712$43-临湘市-5572$37-临沂市-4730$21-凌海市-2272$21-凌源市-2343$41-灵宝市-5053$45-柳州市-6140$52-六盘水市-7020$52-六盘水市-7021$35-龙海市-3991$22-龙井市-2495$37-龙口市-4567$33-龙泉市-3439$35-龙岩市-4050$43-娄底市-5620$14-吕梁市-1730$44-罗定市-5941$41-洛阳市-4930$42-麻城市-5331$34-马鞍山市-3650$15-满洲里市-1962$44-茂名市-5920$22-梅河口市-2454$44-梅州市-5960$41-孟州市-5016$65-米泉市-8852$23-密山市-2758$51-绵阳市-6590$51-绵竹市-6581$34-明光市-3757$23-牡丹江市-2720$35-南安市-3973$36-南昌市-4210$51-南充市-6730$55-南川市-6693$13-南宫市-1321$32-南京市-3010$36-南康市-4283$45-南宁市-6110$35-南平市-4010$32-南通市-3060$44-南雄市-5823$41-南阳市-5130$51-内江市-6630$23-宁安市-2751$33-宁波市-3320$35-宁德市-4030$34-宁国市-3774$51-攀枝花市-6560$21-盘锦市-2320$22-磐石市-2423$37-蓬莱市-4561$36-萍乡市-4230$41-平顶山市-4950$37-平度市-4524$33-平湖市-3352$45-凭祥市-6121$35-莆田市-3940$21-普兰店市-2222$44-普宁市-5867$37-栖霞市-4563$23-七台河市-2740$23-齐齐哈尔市-2640$32-启东市-3066$13-迁安市-1246$42-潜江市-5375$45-钦州市-6311$13-秦皇岛市-1260$37-青岛市-4520$64-青铜峡市-8732$37-青州市-4588$44-清远市-6010$37-曲阜市-4619$53-曲靖市-7360$35-泉州市-3970$52-仁怀市-7042$13-任丘市-1442$37-日照市-4732$37-荣成市-4653$32-如皋市-3062$37-乳山市-4651$41-汝州市-4956$33-瑞安市-3339$36-瑞昌市-4242$36-瑞金市-4296$53-瑞丽市-7546$13-三河市-1461$41-三门峡市-5050$35-三明市-3950$46-三亚市-6420$13-沙河市-1322$44-汕头市-5860$44-汕尾市-5970$41-商丘市-5060$31-上海市-2900$36-上饶市-4330$33-上虞市-3372$44-韶关市-5820$43-韶山市-5533$35-邵武市-4012$43-邵阳市-5550$33-绍兴市-3370$13-深州市-1485$44-深圳市-5840$21-沈阳市-2210$42-十堰市-5230$65-石河子市-9028$13-石家庄市-1210$35-石狮市-3978$42-石首市-5372$64-石嘴山市-8720$51-什邡市-6583$37-寿光市-4582$22-舒兰市-2426$22-双辽市-2433$23-双鸭山市-2680$14-朔州市-1690$44-四会市-5932$22-四平市-2430$22-松原市-2520$42-松滋市-5377$32-苏州市-3050$32-宿迁市-3090$34-宿州市-3740$42-随州市-5286$23-绥芬河市-2757$23-绥化市-2760$51-遂宁市-6620$44-台山市-5892$33-台州市-3450$37-泰安市-4630$32-泰兴市-3124$32-泰州市-3128$32-太仓市-3051$14-太原市-1610$13-唐山市-1240$34-天长市-3752$12-天津市-1100$42-天门市-5374$62-天水市-8250$21-铁法市-2336$23-铁力市-2712$21-铁岭市-2330$22-通化市-2450$15-通辽市-1990$32-通州市-3084$34-桐城市-3681$33-桐乡市-3354$23-同江市-2729$61-铜川市-7920$34-铜陵市-3670$22-图们市-2492$21-瓦房店市-2224$51-万源市-6755$37-威海市-4650$37-潍坊市-4580$61-渭南市-7970$41-卫辉市-4987$33-温岭市-3454$33-温州市-3330$37-文登市-4652$15-乌海市-1930$65-乌鲁木齐市-8810$65-乌苏市-9013$32-无锡市-3020$34-芜湖市-3620$45-梧州市-6210$44-吴川市-5911$32-吴江市-3054$64-吴忠市-8731$13-武安市-1295$42-武汉市-5210$42-武穴市-5332$35-武夷山市-4022$23-五大连池市-2783$65-五家渠市-8858$41-舞钢市-5044$61-西安市-7910$63-西宁市-8510$35-厦门市-3930$42-仙桃市-5371$42-咸宁市-5360$61-咸阳市-7950$42-襄樊市-5280$43-湘潭市-5530$43-湘乡市-5532$41-项城市-5091$42-孝感市-5350$14-孝义市-1734$37-新泰市-4635$41-新乡市-4980$32-新沂市-3036$36-新余市-4260$14-忻州市-1710$41-信阳市-5150$44-信宜市-5921$21-兴城市-2277$32-兴化市-3131$44-兴宁市-5965$61-兴平市-7951$13-邢台市-1310$13-邢台市-1311$32-徐州市-3030$41-许昌市-5030$53-宣威市-7363$15-牙克石市-1964$37-烟台市-4560$32-盐城市-3110$61-延安市-8040$32-扬中市-3143$32-扬州市-3120$44-阳春市-5992$44-阳江市-5990$14-阳泉市-1630$23-伊春市-2710$32-仪征市-3129$51-宜宾市-6710$42-宜昌市-5260$42-宜城市-5282$42-宜都市-5261$32-宜兴市-3023$45-宜州市-6282$41-义马市-5055$33-义乌市-3387$43-益阳市-5610$64-银川市-8710$44-英德市-6012$36-鹰潭市-4270$42-应城市-5352$21-营口市-2280$35-永安市-3960$41-永城市-5069$14-永济市-1812$33-永康市-3382$43-永州市-5650$33-余姚市-3324$37-禹城市-4688$41-禹州市-5034$45-玉林市-6240$62-玉门市-8261$53-玉溪市-7410$14-原平市-1714$43-岳阳市-5570$44-云浮市-5937$14-运城市-1810$42-枣阳市-5288$37-枣庄市-4540$15-扎兰屯市-1963$44-湛江市-5910$36-樟树市-4313$35-漳平市-4056$35-漳州市-3990$32-张家港市-3056$43-张家界市-5590$13-张家口市-1380$37-招远市-4562$23-肇东市-2763$44-肇庆市-5930$32-镇江市-3140$41-郑州市-4910$42-枝江市-5264$44-中山市-6030$64-中卫市-8733$55-重庆市-6530$33-舟山市-3420$41-周口市-5080$44-珠海市-5850$43-株洲市-5520$37-诸城市-4589$33-诸暨市-3375$41-驻马店市-5110$21-庄河市-2223$43-资兴市-5632$51-资阳市-6636$37-淄博市-4530$51-自贡市-6550$37-邹城市-4612$13-遵化市-1248$52-遵义市-7030$41-偃师市-4931$37-兖州市-4611$34-亳州市-3722$32-邳州市-3035$45-岑溪市-6221$33-嵊州市-3373$33-衢州市-3410$51-阆中市-6743$43-沅江市-5612$43-汨罗市-5576$51-泸州市-6570$22-洮南市-2472$13-涿州市-1352$32-溧阳市-3043$41-漯河市-5040$41-濮阳市-5020$22-珲春市-2494$22-桦甸市-2425$37-滕州市-4541$43-耒阳市-5547$22-蛟河市-2424$43-醴陵市-5525$15-阿巴嘎旗-2013$51-阿坝县-6802$51-阿坝州-6790$65-阿合奇-8933$65-阿克苏地区-8910$65-阿克陶-8932$65-阿拉山口-8847$15-阿拉善盟-2080$15-阿拉善右旗-2082$65-阿勒泰地区-9020$54-阿里地区-7810$54-阿里地区普兰县-7811$15-阿鲁科尔沁旗-1941$15-阿荣旗-1965$65-阿瓦提县-8918$54-安多县-7794$36-安福县-4362$43-安化县-5616$33-安吉县-3363$61-安康地区-8010$52-安龙县-7078$13-安平县-1488$43-安仁县-5642$61-安塞县-8045$52-安顺地区-7110$22-安图县-2498$62-安西县-8267$35-安溪县-3974$51-安县-6593$43-安乡县-5581$13-安新县-1363$41-安阳县-4962$36-安远县-4288$51-安岳县-6633$14-安泽县-1779$54-昂仁县-7766$15-敖汉旗-1949$54-八宿县-7726$65-巴楚县-8952$42-巴东县-5414$65-巴里坤县-8842$15-巴林右旗-1943$15-巴林左旗-1942$45-巴马瑶族自治县-6289$51-巴塘县-6826$15-巴彦淖尔盟-2070$65-巴音郭楞蒙古自治州-8880$51-巴中地区-6758$61-白河县-8021$54-白朗县-7768$61-白水县-7979$51-白玉县-6822$13-柏乡县-1325$45-百色地区-6261$65-拜城县-8916$23-拜泉县-2652$14-保德县-1724$43-保靖县-5695$42-保康县-5285$53-保山地区-7530$41-宝丰县-4951$23-宝清县-2724$51-宝兴县-6778$32-宝应县-3122$51-北川县-6595$65-北屯县-8844$21-本溪县-2251$52-毕节地区-7090$54-边坝县-7730$61-彬县-7957$32-滨海县-3112$37-滨州-4660$53-宾川县-7514$54-波密县-7834$41-博爱县-5012$45-博白县-6248$65-博尔塔拉蒙古自治州-8870$65-博湖县-8889$44-博罗县-5952$37-博兴县-4666$13-博野县-1371$23-勃利县-2741$65-布尔津-9022$51-布拖县-6849$33-苍南县-3336$37-苍山县-4734$45-苍梧县-6211$51-苍溪县-6614$53-沧源县-7588$37-曹县-4752$65-策勒县-8966$52-册亨县-7077$43-茶陵县-5523$65-察布察尔锡伯-8993$15-察哈尔右翼后旗-2044$15-察哈尔右翼前旗-2042$15-察哈尔右翼中旗-2043$54-察雅县-7725$54-察隅县-7835$54-昌都地区-7720$65-昌吉州-8850$37-昌乐县-4584$13-昌黎县-1262$53-昌宁县-7535$21-昌图县-2333$43-常宁县-5545$33-常山县-3412$22-长白县-2463$37-长岛县-4566$21-长海县-2225$22-长岭县-2475$51-长宁县-6715$52-长顺县-7159$35-长泰县-3995$35-长汀县-4052$61-长武县-7958$33-长兴县-3362$42-长阳县-5268$41-长垣县-4986$14-长治县-1661$14-长子县-1668$44-潮安营业部-5872$34-巢湖地区-3781$43-辰溪县-5675$15-陈巴尔虎左旗-1974$63-称多县-8583$43-城步县-5559$61-城固县-7993$55-城口县-6681$13-成安县-1288$37-成武县-4754$62-成县-8313$61-澄城县-7978$53-澄江县-7413$13-承德县-1411$13-赤城县-1402$13-崇礼县-1403$36-崇仁县-4375$62-崇信县-8334$42-崇阳县-5365$36-崇义县-4287$53-楚雄州-7380$61-淳化县-7961$13-磁县-1291$43-慈利县-5591$52-从江县-7144$54-措美县-7746$54-措勤县-7816$54-错那县-7750$51-达川地区-6750$15-达尔罕茂明联合旗-2045$15-达拉特旗-2052$51-达县-6752$13-大厂回族自治县-1468$13-大城县-1465$52-大方县-7092$53-大关县-7345$45-大化瑶族自治县-6292$53-大理州-7510$61-大荔县-7976$13-大名县-1281$14-大宁县-1785$44-大埔县-5962$35-大田县-3954$14-大同县-1639$21-大洼县-2321$42-大悟县-5355$45-大新县-6129$23-大兴安岭地区-2790$44-大亚湾营业部-5956$53-大姚县-7386$51-大英县-6623$36-大余县-4285$51-大竹县-6761$14-代县-1715$51-丹巴县-6813$61-丹凤县-8033$51-丹棱县-6665$52-丹寨县-7147$37-单县-4755$41-郸城县-5087$34-当涂县-3651$51-稻城县-6828$43-道县-5654$52-道真县-7036$51-道孚县-6816$36-德安县-4246$45-德保县-6275$51-德昌县-6859$51-德格县-6821$53-德宏州-7540$35-德化县-3976$52-德江县-7057$53-德钦县-7572$33-德清县-3361$44-德庆县-5936$51-得荣县-6829$53-迪庆州-7570$55-垫江县-6692$44-电白县-5923$62-迭部县-8384$54-丁青县-7724$61-定边县-8066$36-定南县-4291$37-定陶县-4753$62-定西地区-8290$14-定襄县-1712$13-定兴县-1356$34-定远县-3755$37-东阿县-4716$43-东安县-5653$22-东丰县-2441$13-东光县-1447$32-东海县-3072$45-东兰县-6288$22-东辽县-2442$37-东明县-4761$23-东宁县-2754$37-东平县-4633$35-东山县-3996$15-东乌珠穆沁旗-2016$36-东乡县-4381$62-东乡县-8367$44-东源县-5985$34-东至县-3792$43-洞口县-5555$33-洞头县-3332$45-都安瑶族自治县-6291$36-都昌县-4248$63-都兰县-8594$52-独山县-7156$23-杜蒙县-2646$15-多伦县-2023$51-峨边彝族自治县-6662$53-峨山县-7417$15-额济纳旗-2083$65-额敏县-9012$15-鄂尔多斯-2050$15-鄂伦春旗-1969$15-鄂托克旗-2055$15-鄂托克前旗-2054$15-鄂温克旗-1971$42-恩施州-5410$53-洱源县-7521$34-繁昌县-3622$14-繁峙县-1716$41-范县-5023$41-方城县-5134$14-方山县-1741$42-房县-5396$13-肥乡县-1286$37-费县-4742$36-分宜县-4261$14-汾西县-1788$55-丰都县-6694$13-丰宁满族自治县-1425$44-丰顺县-5963$32-丰县-3031$44-封开县-5935$41-封丘县-4985$61-风县-7939$55-奉节县-6677$36-奉新县-4315$52-凤冈县-7038$43-凤凰县-5693$53-凤庆县-7582$45-凤山县-6287$34-凤台县-3641$61-凤翔县-7932$34-凤阳县-3756$44-佛冈县-6011$61-佛坪县-8002$61-扶风县-7934$41-扶沟县-5082$45-扶绥县-6127$22-扶余县-2473$36-浮梁县-4222$14-浮山县-1781$55-涪陵区-6690$53-福贡县-7562$65-福海县-9024$13-抚宁县-1263$21-抚顺县-2241$22-抚松县-2461$23-抚远县-2727$61-府谷县-8063$13-阜城县-1492$34-阜南县-3728$32-阜宁县-3113$13-阜平县-1368$21-阜新县-2291$45-富川县-6227$53-富宁县-7458$61-富平县-7982$51-富顺县-6552$61-富县-8049$23-富裕县-2647$53-富源县-7364$65-富蕴县-9023$54-改则县-7815$62-甘谷县-8253$51-甘洛县-6856$23-甘南县-2645$62-甘南州-8380$61-甘泉县-8048$51-甘孜县-6818$51-甘孜州-6810$36-赣县-4282$32-赣榆县-3071$63-刚察县-8544$13-高碑店-1366$37-高青县-4535$62-高台县-8275$37-高唐县-4718$51-高县-6716$13-高阳县-1362$54-革吉县-7814$53-耿马县-7587$54-工布江达县-7831$45-恭城县-6191$42-公安县-5378$54-贡嘎县-7742$54-贡觉县-7722$53-贡山县-7563$13-沽源县-1393$62-古浪县-8283$35-古田县-4035$14-古县-1778$43-古丈县-5696$51-古蔺县-6575$42-谷城县-5284$13-故城县-1489$13-固安县-1462$41-固始县-5157$15-固阳县-1922$64-固原地区-8741$34-固镇县-3633$52-关岭县-7119$37-冠县-4717$13-馆陶县-1294$32-灌南县-3082$45-灌阳县-6185$32-灌云县-3073$41-光山县-5156$35-光泽县-4018$36-广昌县-4382$34-广德县-3773$36-广丰县-4333$62-广河县-8365$14-广灵县-1633$53-广南县-7457$44-广宁县-5933$13-广平县-1287$37-广饶县-4553$13-广宗县-1333$43-桂东县-5641$43-桂阳县-5634$63-贵德县-8563$52-贵定县-7153$63-贵南县-8565$63-果洛州-8570$63-果洛州达日县-8574$63-果洛州甘德县-8573$65-哈巴河-9025$65-哈密地区-8840$65-哈密伊吾县-8843$32-海安县-3061$63-海北州-8540$63-海东-8520$44-海丰县-5971$63-海南州-8560$63-海西州-8590$13-海兴县-1453$33-海盐县-3353$64-海原县-8742$63-海晏县-8543$34-含山县-3784$43-汉寿县-5582$61-汉阴县-8012$51-汉源县-6774$15-杭锦后旗-2077$15-杭锦旗-2056$37-菏泽地区-4750$65-和布克赛尔-9017$65-和静县-8887$44-和平县-5984$14-和顺县-1754$65-和硕县-8888$65-和田地区-8960$34-和县-3785$62-和政县-8366$51-合江县-6572$45-合浦县-6231$62-合水县-8345$61-合阳县-7981$13-河间县-1443$53-河口县-7444$63-河南县-8554$14-河曲县-1723$52-赫章县-7098$42-鹤峰县-5418$53-鹤庆县-7523$21-黑山县-2274$51-黑水县-6798$36-横峰县-4336$61-横山县-8064$43-衡东县-5544$43-衡南县-5542$43-衡山县-5543$43-衡阳县-5541$14-洪洞县-1776$51-洪雅县-6656$32-洪泽县-3087$42-红安县-5334$53-红河县-7441$53-红河州-7430$51-红原县-6804$15-呼伦贝尔盟-1960$23-呼玛县-2791$65-呼图壁县-8853$14-壶关县-1667$36-湖口县-4249$63-互助县-8526$43-花垣县-5694$35-华安县-3999$62-华池县-8344$53-华宁县-7415$53-华坪县-7553$43-华容县-5573$62-华亭县-8335$61-华县-7973$41-滑县-4964$15-化德县-2036$63-化隆县-8527$13-怀安县-1397$44-怀集县-5934$13-怀来县-1399$34-怀宁县-3682$14-怀仁县-1641$34-怀远县-3631$41-淮滨县-5153$41-淮阳县-5088$45-环江毛南族自治县-6284$62-环县-8343$21-桓仁县-2252$37-桓台县-4531$61-黄陵县-8054$61-黄龙县-8053$42-黄梅县-5339$63-黄南州-8550$52-黄平县-7132$22-辉南县-2452$62-徽县-8319$35-惠安县-3971$44-惠东县-5953$44-惠来县-5868$37-惠民县-4662$52-惠水县-7162$36-会昌县-4297$51-会东县-6846$51-会理县-6845$62-会宁县-8242$43-会同县-5681$53-会泽县-7369$14-浑源县-1635$41-获嘉县-4982$65-霍城县-8994$34-霍邱县-3764$34-霍山县-3767$62-积石山-8368$23-鸡东县-2661$13-鸡泽县-1285$34-绩溪县-3777$36-吉安县-4353$65-吉木乃县-9027$65-吉木萨尔县-8857$36-吉水-4354$14-吉县-1782$23-集贤县-2681$43-嘉禾县-5637$54-嘉黎县-7791$33-嘉善县-3351$37-嘉祥县-4616$23-嘉荫县-2711$42-嘉鱼县-5363$51-夹江县-6655$61-佳县-8069$54-加查县-7748$42-监利县-5379$63-尖扎县-8552$53-剑川县-7522$51-剑阁县-6613$52-剑河县-7139$21-建昌县-2345$32-建湖县-3115$35-建宁县-3959$21-建平县-2342$42-建始县-5413$53-建水县-7435$35-将乐县-3957$51-江安县-6714$53-江城县-7477$53-江川县-7412$54-江达县-7721$43-江华县-5657$52-江口县-7052$43-江永县-5656$22-江源县-2464$44-蕉岭县-5967$14-交城县-1733$14-交口县-1744$44-揭东县-5871$44-揭西县-5866$51-金川县-6805$32-金湖县-3089$53-金平县-7442$52-金沙县-7094$62-金塔县-8264$36-金溪县-4378$37-金乡县-4615$45-金秀县-6159$51-金阳县-6851$34-金寨县-3766$52-锦屏县-7138$42-京山县-5382$65-精河县-8872$36-井冈山-4352$51-井研县-6654$53-景东县-7474$53-景谷县-7475$33-景宁畲族自治县-3438$62-景泰县-8243$13-景县-1491$14-静乐县-1718$62-静宁县-8337$36-靖安县-4319$61-靖边县-8065$45-靖西县-6266$22-靖宇县-2462$62-靖远县-8241$43-靖州县-5682$36-九江县-4241$51-九龙县-6814$51-九寨沟-6795$62-酒泉地区-8260$13-巨鹿县-1331$37-巨野县-4756$41-浚县-4971$15-喀喇沁旗-1947$65-喀什地区-8940$65-喀什塔什库尔干县-8953$21-喀左县-2243$41-开封县-4924$33-开化县-3413$51-开江县-6754$15-开鲁县-1995$55-开县-6673$13-康保县-1392$62-康乐县-8363$54-康马县-7770$62-康县-8314$65-柯坪县-8919$15-科尔沁右翼中旗-1983$15-科尔沁左翼后旗-1994$15-科尔沁左翼中旗-1993$23-克东县-2651$65-克拉玛依石油-8820$23-克山县-2649$15-克什克腾旗-1945$65-克孜勒苏柯尔克孜州-8930$37-垦利县-4551$65-库车县-8913$15-库伦旗-1996$13-宽城满族自治县-1421$21-宽甸县-2264$34-来安县-3753$45-来宾县-6155$42-来凤县-5417$43-蓝山县-5658$41-兰考县-4925$53-兰坪县-7564$23-兰西县-2766$53-澜沧县-7479$34-郎溪县-3772$54-朗县-7836$54-浪卡子县-7751$36-乐安县-4376$63-乐都县-8523$13-乐亭县-1245$45-乐业县-6269$51-乐至县-6632$51-雷波县-6858$52-雷山县-7145$54-类乌齐县-7723$22-梨树县-2431$14-黎城县-1666$36-黎川县-4373$52-黎平县-7142$51-理塘县-6825$51-理县-6792$61-礼泉县-7955$62-礼县-8317$52-荔波县-7152$45-荔浦县-6189$53-丽江地区-7550$42-利川县-5412$37-利津县-4552$34-利辛县-3732$36-莲花县-4364$35-连城县-4057$44-连南县-6016$44-连平县-5983$44-连山县-6015$32-涟水县-3085$15-凉城县-2041$51-凉山州-6840$53-梁河县-7543$55-梁平县-6675$37-梁山县-4757$62-两当县-8318$21-辽阳县-2311$23-林甸县-2648$23-林口县-2755$15-林西县-1944$54-林芝地区-7830$53-临沧地区-7580$13-临城县-1323$45-临桂县-6172$34-临泉县-3724$62-临潭区-8381$43-临武县-5638$13-临西县-1337$62-临夏县-8362$62-临夏州-8360$14-临县-1736$37-临邑县-4689$41-临颖县-5042$62-临泽县-8274$13-临漳县-1289$14-临猗县-1814$37-临沭县-4744$62-临洮县-8295$43-临澧县-5584$37-临朐县-4583$51-邻水县-6763$45-凌云县-6268$45-灵川县-6181$14-灵邱县-1634$45-灵山县-6314$14-灵石县-1762$62-灵台县-8333$34-灵璧县-3745$14-陵川县-1684$37-陵县-4683$61-留坝县-8001$45-柳城县-6142$22-柳河县-2453$45-柳江县-6141$14-柳林县-1737$34-六安地区-3760$44-龙川县-5982$23-龙江县-2641$52-龙里县-7161$53-龙陵县-7534$44-龙门县-5954$36-龙南县-4289$43-龙山县-5698$45-龙胜县-6186$33-龙游县-3414$45-龙州县-6133$51-隆昌县-6638$64-隆德县-8744$13-隆化县-1426$43-隆回县-5554$45-隆林县-6272$13-隆尧县-1326$54-隆子县-7749$53-陇川县-7545$62-陇南地区-8310$62-陇西县-8293$61-陇县-7936$51-芦山县-6777$36-芦溪县-4232$13-卢龙县-1264$41-卢氏县-5054$34-庐江县-3782$51-炉霍县-6817$53-鲁甸县-7342$41-鲁山县-4953$62-碌曲县-8386$41-鹿邑县-5086$45-鹿寨县-6152$14-潞城县-1662$53-禄丰县-7391$45-陆川县-6247$44-陆丰县-5972$44-陆河县-5973$53-陆良县-7367$53-绿春县-7443$13-滦南县-1244$13-滦平县-1424$13-滦县-1243$61-略阳县-7998$65-轮台县-8882$23-萝北县-2671$45-罗城仫佬族自治县-6283$52-罗甸县-7158$51-罗江县-6585$53-罗平县-7365$41-罗山县-5159$42-罗田县-5335$61-洛川县-8051$54-洛隆县-7729$61-洛南县-8032$41-洛宁县-4938$65-洛浦县-8965$54-洛扎县-7747$52-麻江县-7146$53-麻栗坡-7454$43-麻阳县-5677$65-玛纳斯县-8854$62-玛曲县-8385$51-马边彝族自治县-6663$53-马关县-7455$53-马龙县-7362$65-麦盖提县-8948$13-满城县-1341$54-芒康县-7728$51-茂县-6793$51-眉山-6652$61-眉县-7935$51-美姑县-6857$62-门源县-8541$34-蒙城县-3727$45-蒙山县-6224$37-蒙阴县-4739$53-蒙自县-7433$13-孟村县-1452$41-孟津县-4932$53-孟连县-7478$53-弥渡县-7515$53-弥勒县-7437$54-米林县-7832$51-米易县-6561$61-米脂县-8068$41-泌阳县-5113$51-冕宁县-6854$61-勉县-7996$65-民丰县-8968$63-民和县-8522$62-民乐县-8273$62-民勤县-8282$41-民权县-5064$23-明水县-2772$35-明溪县-3951$51-名山县-6772$15-莫力达瓦旗-1966$53-墨江县-7473$65-墨玉县-8963$23-漠河县-2793$53-牟定县-7383$65-木垒县-8859$51-木里县-6842$23-穆棱县-2753$45-那坡县-6267$54-那曲地区-7790$51-纳溪县-6573$52-纳雍县-7096$15-奈曼旗-1997$44-南澳县-5863$51-南部县-6734$36-南城县-4372$45-南丹县-6285$36-南丰县-4374$13-南和县-1328$53-南华县-7384$53-南涧县-7516$51-南江县-6757$35-南靖县-3997$41-南乐县-5022$34-南陵县-3623$54-南木林县-7761$13-南皮县-1448$51-南溪县-6713$43-南县-5614$42-南漳县-5283$41-南召县-5133$61-南郑县-7992$41-内黄县-4965$13-内邱县-1324$41-内乡县-5138$23-嫩江县-2784$65-尼勒克-8999$65-鸟恰县-8934$54-聂荣县-7793$15-宁城县-1948$36-宁都县-4293$33-宁海县-3322$35-宁化县-3953$37-宁津县-4693$13-宁晋县-1329$41-宁陵县-5065$45-宁明县-6132$51-宁南县-6847$61-宁强县-7997$61-宁陕县-8014$14-宁武县-1717$62-宁县-8347$37-宁阳县-4631$43-宁远县-5655$53-宁蒗县-7554$53-怒江州-7560$21-盘山县-2322$52-盘县-7022$33-磐安县-3385$32-沛县-3032$51-彭山县-6657$55-彭水苗族土家族自治县-6875$64-彭阳县-8746$36-彭泽县-4251$51-蓬安县-6738$51-蓬溪县-6621$65-皮山县-8964$14-偏关县-1725$52-平坝县-7117$51-平昌县-6759$62-平川区-8244$14-平定县-1651$45-平果县-6264$35-平和县-3998$43-平江县-5575$45-平乐县-6188$61-平利县-8017$62-平凉地区-8330$14-平陆县-1823$64-平罗县-8721$45-平南县-6244$13-平泉县-1423$14-平顺县-1665$52-平塘县-7157$51-平武县-6596$13-平乡县-1334$33-平阳县-3335$14-平遥县-1759$37-平邑县-4741$41-平舆县-5118$37-平原县-4684$44-平远县-5966$53-屏边县-7434$35-屏南县-4036$51-屏山县-6721$35-莆田县-3941$61-蒲城县-7977$14-蒲县-1784$52-普安县-7073$52-普定县-7118$53-普洱县-7472$51-普格县-6848$45-浦北县-6315$35-浦城县-4016$33-浦江县-3384$65-奇台县-8855$61-歧山县-7933$37-齐河县-4687$43-祁东县-5546$63-祁连县-8542$34-祁门县-3714$14-祁县-1758$43-祁阳县-5662$36-铅山县-4335$61-千阳县-7937$13-迁西县-1247$22-乾安县-2479$61-乾县-7954$52-黔东南州-7130$55-黔江地区-6870$52-黔南州-7150$52-黔西南州-7070$52-黔西县-7093$22-前郭县-2476$34-潜山县-3684$53-巧家县-7343$65-且末县-8885$62-秦安县-8252$14-沁水县-1681$14-沁县-1671$41-沁阳县-5014$14-沁源县-1672$51-青川县-6612$23-青冈县-2767$65-青河县-9026$13-青龙县-1261$51-青神县-6659$33-青田县-3432$13-青县-1432$34-青阳县-3794$41-清丰县-5021$13-清河县-1336$61-清涧县-8072$35-清流县-3952$62-清水县-8251$44-清新县-6017$21-清原县-2344$13-清苑县-1342$52-晴隆县-7074$23-庆安县-2771$62-庆城县-8342$62-庆阳地区-8340$33-庆元县-3434$37-庆云县-4694$54-琼结县-7744$53-邱北县-7456$13-邱县-1284$44-曲江县-5821$63-曲麻莱县-8586$54-曲松县-7745$14-曲沃县-1773$13-曲阳县-1367$13-曲周县-1283$51-渠县-6762$34-全椒县-3754$36-全南县-4292$45-全州县-6182$41-确山县-5112$51-壤塘县-6801$23-饶河县-2726$44-饶平县-5862$13-饶阳县-1487$54-仁布县-7769$44-仁化县-5824$51-仁寿县-6651$13-任县-1327$54-日喀则地区-7760$54-日土县-7813$51-荣县-6551$45-融安县-6156$45-融水县-6158$13-容城县-1365$45-容县-6245$32-如东县-3063$44-乳源县-5827$43-汝城县-5639$41-汝南县-5117$41-汝阳县-4936$51-若尔盖县-6803$65-若羌县-8884$54-萨嘎县-7776$54-萨迦县-7764$65-三道岭-9029$52-三都水族自治县-7163$45-三江县-6157$33-三门县-3457$52-三穗县-7134$51-三台县-6591$61-三原县-7952$54-桑日县-7743$43-桑植县-5592$51-色达县-6824$65-莎车县-8946$65-沙湾县-9014$35-沙县-3956$65-沙雅县-8914$42-沙洋县-5383$62-山丹县-8276$54-山南地区-7740$61-山阳县-8035$14-山阴县-1691$41-陕县-5052$41-商城县-5158$15-商都县-2037$61-商洛地区-8030$61-商南县-8034$41-商水县-5084$41-上蔡县-5116$36-上高县-4317$35-上杭县-4054$36-上栗县-4231$36-上饶县-4332$45-上思县-6312$36-上犹县-4286$13-尚义县-1394$43-邵东县-5551$43-邵阳县-5553$33-绍兴县-3371$51-射洪县-6622$32-射阳县-3114$13-涉县-1292$41-社旗县-5141$14-神池县-1719$61-神木县-8062$42-神农架林区-5311$41-沈丘县-5089$53-师宗县-7366$52-施秉县-7133$53-施甸县-7532$36-石城县-4299$14-石楼县-1738$43-石门县-5586$51-石棉县-6775$53-石屏县-7436$51-石渠县-6823$61-石泉县-8013$34-石台县-3793$55-石柱县-6871$52-石阡县-7054$44-始兴县-5822$35-寿宁县-4037$34-寿县-3763$14-寿阳县-1756$34-舒城县-3765$65-疏附县-8942$65-疏勒县-8943$53-双柏县-7382$43-双峰县-5624$53-双江县-7586$43-双牌县-5661$53-水富县-7352$35-顺昌县-4013$13-顺平县-1357$53-思茅地区-7470$52-思南县-7055$15-四子王旗-2046$51-松潘县-6794$52-松桃县-7059$35-松溪县-4019$33-松阳县-3437$15-苏尼特右旗-2015$15-苏尼特左旗-2014$34-宿松县-3686$62-肃北县-8265$62-肃南裕固族自治县-8272$13-肃宁县-1444$23-绥滨县-2672$61-绥德县-8067$53-绥江县-7347$23-绥棱县-2773$43-绥宁县-5557$52-绥阳县-7034$21-绥中县-2271$33-遂昌县-3436$36-遂川县-4359$41-遂平县-5114$44-遂溪县-5913$23-孙吴县-2787$54-索县-7796$65-塔城地区-9010$23-塔河县-2792$21-台安县-2231$52-台江县-7141$41-台前县-5024$36-泰和县-4358$23-泰来县-2644$35-泰宁县-3958$33-泰顺县-3338$61-太白县-7941$14-太谷县-1757$34-太和县-3725$34-太湖县-3685$41-太康县-5085$15-太仆寺旗-2018$41-汤阴县-4963$23-汤原县-2725$13-唐海县-1251$41-唐河县-5142$13-唐县-1358$43-桃江县-5615$43-桃源县-5585$65-特克斯-8998$45-藤县-6222$53-腾冲县-7533$45-天等县-6131$45-天峨县-6286$63-天峻县-8595$51-天全县-6776$33-天台县-3456$14-天镇县-1632$52-天柱县-7137$62-天祝县-8284$45-田东县-6263$45-田林县-6271$45-田阳县-6262$42-通城县-5364$43-通道县-5683$53-通海县-7414$22-通化县-2451$51-通江县-6756$42-通山县-5366$62-通渭县-8292$41-通许县-4922$22-通榆县-2478$41-桐柏县-5144$52-桐梓县-7033$63-同德县-8562$64-同心县-8737$36-铜鼓县-4321$34-铜陵县-3671$52-铜仁地区-7050$32-铜山县-3033$15-突泉县-1985$15-土默特右旗-1921$65-吐鲁番地区-8830$42-团风县-5333$14-屯留县-1664$65-托克逊-8833$65-托里县-9015$36-万安县-4361$36-万年县-4341$13-万全县-1398$14-万荣县-1815$52-万山特区-7061$36-万载县-4316$55-万州区-6670$22-汪清县-2497$51-旺苍县-6611$13-望都县-1359$34-望江县-3687$23-望奎县-2765$52-望谟县-7076$52-威宁县-7097$13-威县-1335$53-威信县-7351$51-威远县-6634$53-巍山县-7517$37-微山县-4613$13-围场满族蒙古族自治县-1427$53-维西县-7573$13-蔚县-1395$13-魏县-1282$62-渭源县-8294$65-尉犁县-8883$41-尉氏县-4923$65-温泉县-8873$65-温宿县-8912$41-温县-5015$13-文安县-1466$33-文成县-3337$53-文山州-7450$14-文水县-1732$62-文县-8315$14-闻喜县-1819$15-翁牛特旗-1946$44-翁源县-5826$52-瓮安县-7155$34-涡阳县-3726$55-巫山县-6678$55-巫溪县-6679$15-乌拉特后旗-2076$15-乌拉特前旗-2074$15-乌拉特中旗-2075$15-乌兰察布盟-2030$63-乌兰县-8593$15-乌审旗-2057$65-乌什县-8917$34-无为县-3783$37-无棣县-4664$34-芜湖县-3621$61-吴堡县-8071$61-吴旗县-8047$13-吴桥县-1446$37-武城县-4686$53-武定县-7389$43-武冈县-5556$61-武功县-7962$55-武隆县-6695$36-武宁县-4243$35-武平县-4055$13-武强县-1486$62-武山县-8254$51-武胜县-6741$62-武威地区-8280$14-武乡县-1669$45-武宣县-6154$13-武邑县-1484$33-武义县-3383$41-武陟县-5013$42-五峰县-5269$34-五河县-3632$44-五华县-5964$37-五莲县-4587$14-五台县-1713$15-五原县-2072$14-五寨县-1721$41-舞阳县-5041$52-务川自治县-7037$14-昔阳县-1755$54-西藏日喀则地区江孜县-7762$54-西藏日喀则地区亚东县-7773$51-西充县-6742$53-西畴县-7453$21-西丰县-2332$62-西和县-8316$41-西华县-5083$64-西吉县-8743$45-西林县-6273$53-西盟县-7481$41-西平县-5115$53-西双版纳州-7490$15-西乌珠穆沁旗-2017$41-西峡县-5135$61-西乡县-7995$15-锡林郭勒盟-2010$41-息县-5152$52-习水县-7044$51-喜德县-6853$35-霞浦县-4033$36-峡江县-4355$62-夏河县-8387$37-夏津县-4685$14-夏县-1821$41-夏邑县-5067$33-仙居县-3455$35-仙游县-3942$42-咸丰县-5416$13-献县-1445$15-镶黄旗-2019$13-香河县-1464$41-襄城县-4955$14-襄汾县-1775$14-襄垣县-1663$43-湘西土家族苗族自治州-5690$43-湘阴县-5574$51-乡城县-6827$14-乡宁县-1783$53-祥云县-7513$32-响水县-3111$33-象山县-3321$45-象州县-6153$34-萧县-3743$51-小金县-6797$42-孝昌县-5351$54-谢通门县-7767$41-新安县-4933$15-新巴尔虎右旗-1972$15-新巴尔虎左旗-1973$21-新宾县-2242$41-新蔡县-5119$33-新昌县-3374$44-新丰县-5828$36-新干县-4356$65-新和县-8915$13-新河县-1332$43-新化县-5625$43-新晃县-5678$51-新龙县-6819$43-新宁县-5558$53-新平县-7418$43-新邵县-5552$43-新田县-5659$41-新县-5161$41-新乡县-4981$44-新兴县-5938$41-新野县-5143$65-新源县-8996$14-新绛县-1816$45-忻城县-6161$36-信丰县-4284$36-星子县-4247$15-兴安盟-1980$45-兴安县-6183$36-兴国县-4295$63-兴海县-8564$15-兴和县-2038$13-兴隆县-1422$52-兴仁县-7072$42-兴山县-5266$51-兴文县-6719$14-兴县-1735$45-兴业县-6249$13-雄县-1364$34-休宁县-3712$36-修水县-4244$41-修武县-5011$55-秀山土家族苗族自治县-6872$13-徐水县-1354$44-徐闻县-5915$41-许昌县-5032$51-叙永县-6574$34-宣城地区-3771$42-宣恩县-5415$51-宣汉县-6753$13-宣化县-1381$63-循化县-8528$61-旬阳县-8019$61-旬邑县-7959$36-寻乌县-4298$23-逊克县-2786$51-雅安地区-6770$51-雅江县-6815$65-焉耆县-8886$51-盐边县-6562$64-盐池县-8736$53-盐津县-7344$13-盐山县-1449$51-盐亭县-6592$51-盐源县-6843$22-延边自治州-2490$61-延长县-8042$61-延川县-8043$41-延津县-4984$43-炎陵县-5526$52-沿河县-7058$53-砚山-7452$61-洋县-7994$14-阳城县-1682$44-阳东县-5993$14-阳高县-1631$37-阳谷县-4713$44-阳山县-6013$45-阳朔县-6171$44-阳西县-5991$42-阳新县-5367$37-阳信县-4663$13-阳原县-1396$53-漾濞县-7512$53-姚安县-7385$61-耀县-7921$65-叶城县-8947$41-叶县-4952$23-依安县-2643$41-伊川县-4939$15-伊金霍勒旗-2058$65-伊犁巩留县-8995$65-伊犁州-8980$65-伊宁县-8992$22-伊通县-2432$51-仪陇县-6739$37-沂南县-4743$37-沂水县-4737$37-沂源县-4536$51-宜宾县-6712$42-宜昌县-5263$61-宜川县-8052$36-宜春地区-4310$36-宜丰县-4318$36-宜黄县-4377$61-宜君县-7922$41-宜阳县-4937$43-宜章县-5636$53-彝良县-7349$53-易门县-7416$13-易县-1353$21-义县-2275$14-翼城县-1774$52-印江县-7056$65-英吉沙县-8944$42-英山县-5336$14-应县-1636$51-营山县-6736$53-盈江县-7544$62-永昌县-8231$35-永春县-3975$53-永德县-7584$35-永定县-4053$36-永丰县-4357$45-永福县-6184$14-永和县-1786$22-永吉县-2421$33-永嘉县-3334$62-永靖县-8364$13-永年县-1293$53-永平县-7518$13-永清县-1463$53-永仁县-7387$53-永善县-7346$53-永胜县-7552$61-永寿县-7956$43-永顺县-5697$36-永新县-4363$43-永兴县-5635$36-永修县-4245$35-尤溪县-3955$55-酉阳土家族苗族自治县-6874$23-友谊县-2728$14-右玉县-1638$36-于都县-4294$65-于田县-8967$14-盂县-1652$61-榆林地区-8060$14-榆社县-1752$41-虞城县-5062$36-余干县-4338$36-余江县-4272$52-余庆县-7041$37-鱼台县-4614$33-玉环县-3458$52-玉屏侗族自治县-7053$36-玉山县-4334$63-玉树州-8580$13-玉田县-1249$44-郁南县-5939$65-裕民县-9016$53-元江县-7419$53-元谋县-7388$53-元阳县-7439$14-垣曲县-1824$41-原阳县-4983$42-远安县-5265$51-越西县-6855$51-岳池县-6735$65-岳普湖县-8949$34-岳西县-3688$43-岳阳县-5571$33-云和县-3433$53-云龙县-7519$42-云梦县-5356$53-云县-7583$35-云霄县-3992$55-云阳县-6676$42-郧西县-5393$42-郧县-5392$63-杂多县-8582$13-枣强县-1483$63-泽库县-8553$65-泽普县-8945$15-扎鲁特旗-1998$54-扎囊县-7741$15-扎赉特旗-1984$54-札达县-7812$37-沾化县-4665$53-沾益县-7371$54-樟木口岸-7711$21-彰武县-2292$35-漳浦县-3993$62-漳县-8296$13-张北县-1391$62-张家川-8255$62-张掖地区-8270$51-昭觉县-6852$45-昭平县-6223$65-昭苏县-8997$53-昭通地区-7340$23-肇源县-2768$23-肇州县-2769$52-贞丰县-7075$61-镇安县-8036$61-镇巴县-7999$53-镇康县-7585$52-镇宁县-7121$61-镇坪县-8018$41-镇平县-5137$53-镇雄县-7348$62-镇原县-8348$53-镇源县-7476$52-镇远县-7135$22-镇赉县-2477$52-正安县-7035$15-正蓝旗-2022$62-正宁县-8346$15-正镶白旗-2021$41-正阳县-5121$35-政和县-4021$52-织金县-7095$61-志丹县-8046$51-中江县-6582$64-中宁县-8734$14-中阳县-1743$55-忠县-6674$45-钟山县-6226$42-钟祥县-5381$62-舟曲县-8383$35-周宁县-4038$43-株洲县-5521$42-竹山县-5394$42-竹溪县-5395$62-庄浪县-8336$15-准格尔旗-2053$62-卓尼县-8382$15-卓资县-2035$36-资溪县-4379$45-资源县-6187$51-资中县-6635$44-紫金县-5981$61-紫阳县-8015$52-紫云县-7122$61-子长县-8044$61-子洲县-8073$37-邹平县-4667$52-遵义县-7032$54-左贡县-7727$14-左权县-1753$14-左云县-1637$61-柞水县-8037$43-攸县-5522$65-伽师县-8951$23-讷河县-2642$35-诏安县-3994$14-隰县-1787$41-郏县-4954$37-郓城县-4758$37-郯城县-4733$41-郾城县-5043$37-鄄城县-4759$41-鄢陵县-5033$36-鄱阳县-4339$65-鄯善县-8832$53-勐海县-7492$53-勐腊县-7493$43-芷江县-5679$14-芮城县-1813$37-茌平县-4715$37-莒南县-4735$37-莒县-4736$51-荥经县-6773$37-莘县-4714$42-蕲春县-5338$36-弋阳县-4337$52-岑巩县-7136$61-岚皋县-8016$14-岚县-1739$14-岢岚县-1722$21-岫岩县-2265$33-岱山县-3421$62-岷县-8297$33-嵊泗县-3422$41-嵩县-4935$43-沅陵县-5674$51-沐川县-6658$51-汶川县-6791$37-汶上县-4617$32-沭阳县-3083$51-泸定县-6812$53-泸西县-7438$43-泸溪县-5692$51-泸县-6571$32-泗洪县-3086$37-泗水县-4618$34-泗县-3746$32-泗阳县-3093$62-泾川县-8332$34-泾县-3775$61-泾阳县-7953$64-泾源县-8745$13-涞水县-1361$13-涞源县-1355$42-浠水县-5337$41-淇县-4972$41-淅川县-5139$13-涿鹿县-1401$41-渑池县-5051$43-溆浦县-5676$52-湄潭县-7039$41-潢川县-5155$61-潼关县-7975$34-濉溪县-3661$43-澧县-5583$41-濮阳县-5025$62-宕昌县-8312$36-婺源县-4343$14-绛县-1822$33-缙云县-3435$51-珙县-6718$41-杞县-4921$34-枞阳县-3683$41-柘城县-5068$35-柘荣县-4039$23-桦川县-2723$23-桦南县-2721$41-栾川县-4934$51-梓潼县-6594$52-榕江县-7143$51-犍为县-6653$34-歙县-3711$34-旌德县-3776$34-砀山县-3742$15-磴口县-2073$32-盱眙县-3088$32-睢宁县-3034$41-睢县-5066$42-秭归县-5267$14-稷山县-1817$34-颍上县-3729$13-蠡县-1372$51-筠连县-6717$61-麟游县-7938$34-黟县-3713";
    var bank = "102-中国工商银行$103-中国农业银行$104-中国银行$105-中国建设银行$301-交通银行$308-招商银行$302-中信银行$303-中国光大银行$304-华夏银行$305-中国民生银行$306-广东发展银行$309-兴业银行$310-上海浦东发展银行$313-城市商业银行$402-农村信用联社$315-恒丰银行$316-浙商银行$314-农村商业银行$401-城市信用联社$317-农村合作银行$319-徽商银行股份有限公司$318-渤海银行股份有限公司$403-邮政储蓄$313-北京银行$313-南京银行$313-江苏银行$313-宁波银行$313-上海银行$313-杭州银行$402-东莞农村商业银行$201-国家开发银行$202-中国进出口银行$203-中国农业发展银行$501-汇丰银行$502-东亚银行$671-渣打银行$712-德意志银行$503-南洋商业银行$504-恒生银行$531-花旗银行$532-美国银行$595-新韩银行$591-韩国外换银行$562-日本日联银行$593-友利银行$561-日本东京三菱银行$563-日本三井住友银行$564-日本瑞穗实业银行$596-韩国中小企业银行$321-重庆三峡银行$510-永亨银行$622-大华银行$623-新加坡星展银行$691-法国兴业银行$781-厦门国际银行$782-法国巴黎银行$787-华一银行$320-村镇银行$322-上海农村商业银行$513-大新银行$785-华商银行$717-中德住房储蓄银行$323-民营银行$313-广东华兴银行$906-国泰君安$907-包商银行$908-昆仑银行$909-长安银行";
//开始处理
var banks = bank.split('$'),arr=[],banklist;
var banklist_html;

if(ssbank){
	banklist_html="<option value='"+ssbank+"'>"+ssbank+"</option>";
}else{
	banklist_html="<option value=''>请选择</option>";
}

for(var i=0,l=banks.length;i<l;i++){
       banklist = banks[i].split('-');
        //arr.push({text:list[1],value:list[0]})
		banklist_html+="<option value='"+banklist[1]+"'>"+banklist[1]+"</option>";
};
$("#s_bank").html(banklist_html);
//开户行city
var citys = province.split('$'),arr=[],citylist;
var banklist_html;

if(bankss){
	citylist_html="<option value='"+bankss+"'>"+bankss+"</option>";
}else{
	citylist_html="<option value=''>请选择</option>";
}

for(var i=0,l=citys.length;i<l;i++){
       citylist = citys[i].split('-');
        //arr.push({text:list[1],value:list[0]})
		citylist_html+="<option value='"+citylist[0]+"'>"+citylist[0]+"</option>";
};
$("#s_city").html(citylist_html);
//区县
var bankxx = city.split('$'),arr=[],bankxlist;
 var bankx_html;

	if(bankxs){
		bankx_html="<option value='"+bankxs+"'>"+bankxs+"</option>";
	}else{
		bankx_html="<option value=''>请选择</option>";
	}
	var sz;
	 $("#s_city").change(function()
	   {
		   
		    sz=$("#s_city").find('option:selected').text();
		     for(var i=0,l=citys.length;i<l;i++){
             citylist = citys[i].split('-');
		      if(sz==citylist[0]){
				  sz=citylist[1];
			  }
             };
			 bankx_html="";
			 for(var i=0,l=bankxx.length;i<l;i++){
			   bankxlist = bankxx[i].split('-');
				//arr.push({text:list[1],value:list[0]})
				if(sz==bankxlist[0]){
				bankx_html+="<option value='"+bankxlist[1]+"'>"+bankxlist[1]+"</option>";
				}
			 };
          $("#s_xian").html(bankx_html);
      });
$("#s_xian").html(bankx_html);
});