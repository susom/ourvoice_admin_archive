<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/cloud/tasks/v2beta2/cloudtasks.proto

namespace GPBMetadata\Google\Cloud\Tasks\V2Beta2;

class Cloudtasks
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        \GPBMetadata\Google\Api\Annotations::initOnce();
        \GPBMetadata\Google\Api\Client::initOnce();
        \GPBMetadata\Google\Api\FieldBehavior::initOnce();
        \GPBMetadata\Google\Api\Resource::initOnce();
        \GPBMetadata\Google\Cloud\Tasks\V2Beta2\Queue::initOnce();
        \GPBMetadata\Google\Cloud\Tasks\V2Beta2\Task::initOnce();
        \GPBMetadata\Google\Iam\V1\IamPolicy::initOnce();
        \GPBMetadata\Google\Iam\V1\Policy::initOnce();
        \GPBMetadata\Google\Protobuf\Duration::initOnce();
        \GPBMetadata\Google\Protobuf\GPBEmpty::initOnce();
        \GPBMetadata\Google\Protobuf\FieldMask::initOnce();
        \GPBMetadata\Google\Protobuf\Timestamp::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0a8e350a2b676f6f676c652f636c6f75642f7461736b732f763262657461" .
            "322f636c6f75647461736b732e70726f746f121a676f6f676c652e636c6f" .
            "75642e7461736b732e763262657461321a17676f6f676c652f6170692f63" .
            "6c69656e742e70726f746f1a1f676f6f676c652f6170692f6669656c645f" .
            "6265686176696f722e70726f746f1a19676f6f676c652f6170692f726573" .
            "6f757263652e70726f746f1a26676f6f676c652f636c6f75642f7461736b" .
            "732f763262657461322f71756575652e70726f746f1a25676f6f676c652f" .
            "636c6f75642f7461736b732f763262657461322f7461736b2e70726f746f" .
            "1a1e676f6f676c652f69616d2f76312f69616d5f706f6c6963792e70726f" .
            "746f1a1a676f6f676c652f69616d2f76312f706f6c6963792e70726f746f" .
            "1a1e676f6f676c652f70726f746f6275662f6475726174696f6e2e70726f" .
            "746f1a1b676f6f676c652f70726f746f6275662f656d7074792e70726f74" .
            "6f1a20676f6f676c652f70726f746f6275662f6669656c645f6d61736b2e" .
            "70726f746f1a1f676f6f676c652f70726f746f6275662f74696d65737461" .
            "6d702e70726f746f2283010a114c69737451756575657352657175657374" .
            "12370a06706172656e741801200128094227e04102fa4121121f636c6f75" .
            "647461736b732e676f6f676c65617069732e636f6d2f5175657565120e0a" .
            "0666696c74657218022001280912110a09706167655f73697a6518032001" .
            "280512120a0a706167655f746f6b656e18042001280922600a124c697374" .
            "517565756573526573706f6e736512310a0671756575657318012003280b" .
            "32212e676f6f676c652e636c6f75642e7461736b732e763262657461322e" .
            "517565756512170a0f6e6578745f706167655f746f6b656e180220012809" .
            "22480a0f47657451756575655265717565737412350a046e616d65180120" .
            "0128094227e04102fa41210a1f636c6f75647461736b732e676f6f676c65" .
            "617069732e636f6d2f51756575652284010a124372656174655175657565" .
            "5265717565737412370a06706172656e741801200128094227e04102fa41" .
            "21121f636c6f75647461736b732e676f6f676c65617069732e636f6d2f51" .
            "7565756512350a05717565756518022001280b32212e676f6f676c652e63" .
            "6c6f75642e7461736b732e763262657461322e51756575654203e0410222" .
            "7c0a1255706461746551756575655265717565737412350a057175657565" .
            "18012001280b32212e676f6f676c652e636c6f75642e7461736b732e7632" .
            "62657461322e51756575654203e04102122f0a0b7570646174655f6d6173" .
            "6b18022001280b321a2e676f6f676c652e70726f746f6275662e4669656c" .
            "644d61736b224b0a1244656c65746551756575655265717565737412350a" .
            "046e616d651801200128094227e04102fa41210a1f636c6f75647461736b" .
            "732e676f6f676c65617069732e636f6d2f5175657565224a0a1150757267" .
            "6551756575655265717565737412350a046e616d651801200128094227e0" .
            "4102fa41210a1f636c6f75647461736b732e676f6f676c65617069732e63" .
            "6f6d2f5175657565224a0a11506175736551756575655265717565737412" .
            "350a046e616d651801200128094227e04102fa41210a1f636c6f75647461" .
            "736b732e676f6f676c65617069732e636f6d2f5175657565224b0a125265" .
            "73756d6551756575655265717565737412350a046e616d65180120012809" .
            "4227e04102fa41210a1f636c6f75647461736b732e676f6f676c65617069" .
            "732e636f6d2f517565756522af010a104c6973745461736b735265717565" .
            "737412360a06706172656e741801200128094226e04102fa4120121e636c" .
            "6f75647461736b732e676f6f676c65617069732e636f6d2f5461736b123c" .
            "0a0d726573706f6e73655f7669657718022001280e32252e676f6f676c65" .
            "2e636c6f75642e7461736b732e763262657461322e5461736b2e56696577" .
            "12110a09706167655f73697a6518042001280512120a0a706167655f746f" .
            "6b656e180520012809225d0a114c6973745461736b73526573706f6e7365" .
            "122f0a057461736b7318012003280b32202e676f6f676c652e636c6f7564" .
            "2e7461736b732e763262657461322e5461736b12170a0f6e6578745f7061" .
            "67655f746f6b656e1802200128092284010a0e4765745461736b52657175" .
            "65737412340a046e616d651801200128094226e04102fa41200a1e636c6f" .
            "75647461736b732e676f6f676c65617069732e636f6d2f5461736b123c0a" .
            "0d726573706f6e73655f7669657718022001280e32252e676f6f676c652e" .
            "636c6f75642e7461736b732e763262657461322e5461736b2e5669657722" .
            "be010a114372656174655461736b5265717565737412360a06706172656e" .
            "741801200128094226e04102fa4120121e636c6f75647461736b732e676f" .
            "6f676c65617069732e636f6d2f5461736b12330a047461736b1802200128" .
            "0b32202e676f6f676c652e636c6f75642e7461736b732e76326265746132" .
            "2e5461736b4203e04102123c0a0d726573706f6e73655f76696577180320" .
            "01280e32252e676f6f676c652e636c6f75642e7461736b732e7632626574" .
            "61322e5461736b2e5669657722490a1144656c6574655461736b52657175" .
            "65737412340a046e616d651801200128094226e04102fa41200a1e636c6f" .
            "75647461736b732e676f6f676c65617069732e636f6d2f5461736b22e401" .
            "0a114c656173655461736b735265717565737412360a06706172656e7418" .
            "01200128094226e04102fa4120121e636c6f75647461736b732e676f6f67" .
            "6c65617069732e636f6d2f5461736b12110a096d61785f7461736b731802" .
            "2001280512360a0e6c656173655f6475726174696f6e18032001280b3219" .
            "2e676f6f676c652e70726f746f6275662e4475726174696f6e4203e04102" .
            "123c0a0d726573706f6e73655f7669657718042001280e32252e676f6f67" .
            "6c652e636c6f75642e7461736b732e763262657461322e5461736b2e5669" .
            "6577120e0a0666696c74657218052001280922450a124c65617365546173" .
            "6b73526573706f6e7365122f0a057461736b7318012003280b32202e676f" .
            "6f676c652e636c6f75642e7461736b732e763262657461322e5461736b22" .
            "86010a1641636b6e6f776c656467655461736b5265717565737412340a04" .
            "6e616d651801200128094226e04102fa41200a1e636c6f75647461736b73" .
            "2e676f6f676c65617069732e636f6d2f5461736b12360a0d736368656475" .
            "6c655f74696d6518022001280b321a2e676f6f676c652e70726f746f6275" .
            "662e54696d657374616d704203e0410222f7010a1152656e65774c656173" .
            "655265717565737412340a046e616d651801200128094226e04102fa4120" .
            "0a1e636c6f75647461736b732e676f6f676c65617069732e636f6d2f5461" .
            "736b12360a0d7363686564756c655f74696d6518022001280b321a2e676f" .
            "6f676c652e70726f746f6275662e54696d657374616d704203e041021236" .
            "0a0e6c656173655f6475726174696f6e18032001280b32192e676f6f676c" .
            "652e70726f746f6275662e4475726174696f6e4203e04102123c0a0d7265" .
            "73706f6e73655f7669657718042001280e32252e676f6f676c652e636c6f" .
            "75642e7461736b732e763262657461322e5461736b2e5669657722c0010a" .
            "1243616e63656c4c656173655265717565737412340a046e616d65180120" .
            "0128094226e04102fa41200a1e636c6f75647461736b732e676f6f676c65" .
            "617069732e636f6d2f5461736b12360a0d7363686564756c655f74696d65" .
            "18022001280b321a2e676f6f676c652e70726f746f6275662e54696d6573" .
            "74616d704203e04102123c0a0d726573706f6e73655f7669657718032001" .
            "280e32252e676f6f676c652e636c6f75642e7461736b732e763262657461" .
            "322e5461736b2e566965772284010a0e52756e5461736b52657175657374" .
            "12340a046e616d651801200128094226e04102fa41200a1e636c6f756474" .
            "61736b732e676f6f676c65617069732e636f6d2f5461736b123c0a0d7265" .
            "73706f6e73655f7669657718022001280e32252e676f6f676c652e636c6f" .
            "75642e7461736b732e763262657461322e5461736b2e5669657732d41c0a" .
            "0a436c6f75645461736b7312ad010a0a4c697374517565756573122d2e67" .
            "6f6f676c652e636c6f75642e7461736b732e763262657461322e4c697374" .
            "517565756573526571756573741a2e2e676f6f676c652e636c6f75642e74" .
            "61736b732e763262657461322e4c697374517565756573526573706f6e73" .
            "65224082d3e4930231122f2f763262657461322f7b706172656e743d7072" .
            "6f6a656374732f2a2f6c6f636174696f6e732f2a7d2f717565756573da41" .
            "06706172656e74129a010a084765745175657565122b2e676f6f676c652e" .
            "636c6f75642e7461736b732e763262657461322e47657451756575655265" .
            "71756573741a212e676f6f676c652e636c6f75642e7461736b732e763262" .
            "657461322e5175657565223e82d3e4930231122f2f763262657461322f7b" .
            "6e616d653d70726f6a656374732f2a2f6c6f636174696f6e732f2a2f7175" .
            "657565732f2a7dda41046e616d6512af010a0b4372656174655175657565" .
            "122e2e676f6f676c652e636c6f75642e7461736b732e763262657461322e" .
            "4372656174655175657565526571756573741a212e676f6f676c652e636c" .
            "6f75642e7461736b732e763262657461322e5175657565224d82d3e49302" .
            "38222f2f763262657461322f7b706172656e743d70726f6a656374732f2a" .
            "2f6c6f636174696f6e732f2a7d2f7175657565733a057175657565da410c" .
            "706172656e742c717565756512ba010a0b5570646174655175657565122e" .
            "2e676f6f676c652e636c6f75642e7461736b732e763262657461322e5570" .
            "646174655175657565526571756573741a212e676f6f676c652e636c6f75" .
            "642e7461736b732e763262657461322e5175657565225882d3e493023e32" .
            "352f763262657461322f7b71756575652e6e616d653d70726f6a65637473" .
            "2f2a2f6c6f636174696f6e732f2a2f7175657565732f2a7d3a0571756575" .
            "65da411171756575652c7570646174655f6d61736b1295010a0b44656c65" .
            "74655175657565122e2e676f6f676c652e636c6f75642e7461736b732e76" .
            "3262657461322e44656c6574655175657565526571756573741a162e676f" .
            "6f676c652e70726f746f6275662e456d707479223e82d3e49302312a2f2f" .
            "763262657461322f7b6e616d653d70726f6a656374732f2a2f6c6f636174" .
            "696f6e732f2a2f7175657565732f2a7dda41046e616d6512a7010a0a5075" .
            "7267655175657565122d2e676f6f676c652e636c6f75642e7461736b732e" .
            "763262657461322e50757267655175657565526571756573741a212e676f" .
            "6f676c652e636c6f75642e7461736b732e763262657461322e5175657565" .
            "224782d3e493023a22352f763262657461322f7b6e616d653d70726f6a65" .
            "6374732f2a2f6c6f636174696f6e732f2a2f7175657565732f2a7d3a7075" .
            "7267653a012ada41046e616d6512a7010a0a50617573655175657565122d" .
            "2e676f6f676c652e636c6f75642e7461736b732e763262657461322e5061" .
            "7573655175657565526571756573741a212e676f6f676c652e636c6f7564" .
            "2e7461736b732e763262657461322e5175657565224782d3e493023a2235" .
            "2f763262657461322f7b6e616d653d70726f6a656374732f2a2f6c6f6361" .
            "74696f6e732f2a2f7175657565732f2a7d3a70617573653a012ada41046e" .
            "616d6512aa010a0b526573756d655175657565122e2e676f6f676c652e63" .
            "6c6f75642e7461736b732e763262657461322e526573756d655175657565" .
            "526571756573741a212e676f6f676c652e636c6f75642e7461736b732e76" .
            "3262657461322e5175657565224882d3e493023b22362f76326265746132" .
            "2f7b6e616d653d70726f6a656374732f2a2f6c6f636174696f6e732f2a2f" .
            "7175657565732f2a7d3a726573756d653a012ada41046e616d6512a1010a" .
            "0c47657449616d506f6c69637912222e676f6f676c652e69616d2e76312e" .
            "47657449616d506f6c696379526571756573741a152e676f6f676c652e69" .
            "616d2e76312e506f6c696379225682d3e493024522402f76326265746132" .
            "2f7b7265736f757263653d70726f6a656374732f2a2f6c6f636174696f6e" .
            "732f2a2f7175657565732f2a7d3a67657449616d506f6c6963793a012ada" .
            "41087265736f7572636512a8010a0c53657449616d506f6c69637912222e" .
            "676f6f676c652e69616d2e76312e53657449616d506f6c69637952657175" .
            "6573741a152e676f6f676c652e69616d2e76312e506f6c696379225d82d3" .
            "e493024522402f763262657461322f7b7265736f757263653d70726f6a65" .
            "6374732f2a2f6c6f636174696f6e732f2a2f7175657565732f2a7d3a7365" .
            "7449616d506f6c6963793a012ada410f7265736f757263652c706f6c6963" .
            "7912d3010a125465737449616d5065726d697373696f6e7312282e676f6f" .
            "676c652e69616d2e76312e5465737449616d5065726d697373696f6e7352" .
            "6571756573741a292e676f6f676c652e69616d2e76312e5465737449616d" .
            "5065726d697373696f6e73526573706f6e7365226882d3e493024b22462f" .
            "763262657461322f7b7265736f757263653d70726f6a656374732f2a2f6c" .
            "6f636174696f6e732f2a2f7175657565732f2a7d3a7465737449616d5065" .
            "726d697373696f6e733a012ada41147265736f757263652c7065726d6973" .
            "73696f6e7312b2010a094c6973745461736b73122c2e676f6f676c652e63" .
            "6c6f75642e7461736b732e763262657461322e4c6973745461736b735265" .
            "71756573741a2d2e676f6f676c652e636c6f75642e7461736b732e763262" .
            "657461322e4c6973745461736b73526573706f6e7365224882d3e4930239" .
            "12372f763262657461322f7b706172656e743d70726f6a656374732f2a2f" .
            "6c6f636174696f6e732f2a2f7175657565732f2a7d2f7461736b73da4106" .
            "706172656e74129f010a074765745461736b122a2e676f6f676c652e636c" .
            "6f75642e7461736b732e763262657461322e4765745461736b5265717565" .
            "73741a202e676f6f676c652e636c6f75642e7461736b732e763262657461" .
            "322e5461736b224682d3e493023912372f763262657461322f7b6e616d65" .
            "3d70726f6a656374732f2a2f6c6f636174696f6e732f2a2f717565756573" .
            "2f2a2f7461736b732f2a7dda41046e616d6512af010a0a43726561746554" .
            "61736b122d2e676f6f676c652e636c6f75642e7461736b732e7632626574" .
            "61322e4372656174655461736b526571756573741a202e676f6f676c652e" .
            "636c6f75642e7461736b732e763262657461322e5461736b225082d3e493" .
            "023c22372f763262657461322f7b706172656e743d70726f6a656374732f" .
            "2a2f6c6f636174696f6e732f2a2f7175657565732f2a7d2f7461736b733a" .
            "012ada410b706172656e742c7461736b129b010a0a44656c657465546173" .
            "6b122d2e676f6f676c652e636c6f75642e7461736b732e76326265746132" .
            "2e44656c6574655461736b526571756573741a162e676f6f676c652e7072" .
            "6f746f6275662e456d707479224682d3e49302392a372f76326265746132" .
            "2f7b6e616d653d70726f6a656374732f2a2f6c6f636174696f6e732f2a2f" .
            "7175657565732f2a2f7461736b732f2a7dda41046e616d6512cd010a0a4c" .
            "656173655461736b73122d2e676f6f676c652e636c6f75642e7461736b73" .
            "2e763262657461322e4c656173655461736b73526571756573741a2e2e67" .
            "6f6f676c652e636c6f75642e7461736b732e763262657461322e4c656173" .
            "655461736b73526573706f6e7365226082d3e4930242223d2f7632626574" .
            "61322f7b706172656e743d70726f6a656374732f2a2f6c6f636174696f6e" .
            "732f2a2f7175657565732f2a7d2f7461736b733a6c656173653a012ada41" .
            "15706172656e742c6c656173655f6475726174696f6e12c2010a0f41636b" .
            "6e6f776c656467655461736b12322e676f6f676c652e636c6f75642e7461" .
            "736b732e763262657461322e41636b6e6f776c656467655461736b526571" .
            "756573741a162e676f6f676c652e70726f746f6275662e456d7074792263" .
            "82d3e493024822432f763262657461322f7b6e616d653d70726f6a656374" .
            "732f2a2f6c6f636174696f6e732f2a2f7175657565732f2a2f7461736b73" .
            "2f2a7d3a61636b6e6f776c656467653a012ada41126e616d652c73636865" .
            "64756c655f74696d6512d0010a0a52656e65774c65617365122d2e676f6f" .
            "676c652e636c6f75642e7461736b732e763262657461322e52656e65774c" .
            "65617365526571756573741a202e676f6f676c652e636c6f75642e746173" .
            "6b732e763262657461322e5461736b227182d3e493024722422f76326265" .
            "7461322f7b6e616d653d70726f6a656374732f2a2f6c6f636174696f6e73" .
            "2f2a2f7175657565732f2a2f7461736b732f2a7d3a72656e65774c656173" .
            "653a012ada41216e616d652c7363686564756c655f74696d652c6c656173" .
            "655f6475726174696f6e12c4010a0b43616e63656c4c65617365122e2e67" .
            "6f6f676c652e636c6f75642e7461736b732e763262657461322e43616e63" .
            "656c4c65617365526571756573741a202e676f6f676c652e636c6f75642e" .
            "7461736b732e763262657461322e5461736b226382d3e493024822432f76" .
            "3262657461322f7b6e616d653d70726f6a656374732f2a2f6c6f63617469" .
            "6f6e732f2a2f7175657565732f2a2f7461736b732f2a7d3a63616e63656c" .
            "4c656173653a012ada41126e616d652c7363686564756c655f74696d6512" .
            "a6010a0752756e5461736b122a2e676f6f676c652e636c6f75642e746173" .
            "6b732e763262657461322e52756e5461736b526571756573741a202e676f" .
            "6f676c652e636c6f75642e7461736b732e763262657461322e5461736b22" .
            "4d82d3e4930240223b2f763262657461322f7b6e616d653d70726f6a6563" .
            "74732f2a2f6c6f636174696f6e732f2a2f7175657565732f2a2f7461736b" .
            "732f2a7d3a72756e3a012ada41046e616d651a4dca4119636c6f75647461" .
            "736b732e676f6f676c65617069732e636f6dd2412e68747470733a2f2f77" .
            "77772e676f6f676c65617069732e636f6d2f617574682f636c6f75642d70" .
            "6c6174666f726d427c0a1e636f6d2e676f6f676c652e636c6f75642e7461" .
            "736b732e76326265746132420f436c6f75645461736b7350726f746f5001" .
            "5a3f676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f67" .
            "6f6f676c65617069732f636c6f75642f7461736b732f763262657461323b" .
            "7461736b73a202055441534b53620670726f746f33"
        ), true);

        static::$is_initialized = true;
    }
}
