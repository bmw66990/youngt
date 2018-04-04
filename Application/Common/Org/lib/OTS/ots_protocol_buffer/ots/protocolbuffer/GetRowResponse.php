<?php
namespace ots\protocolbuffer;

// @@protoc_insertion_point(namespace:.ots.protocolbuffer.GetRowResponse)

/**
 * Generated by the protocol buffer compiler.  DO NOT EDIT!
 * source: ots_protocol_buffer.proto
 *
 * -*- magic methods -*-
 *
 * @method \ots\protocolbuffer\ConsumedCapacity getConsumed()
 * @method void setConsumed(\ots\protocolbuffer\ConsumedCapacity $value)
 * @method \ots\protocolbuffer\Row getRow()
 * @method void setRow(\ots\protocolbuffer\Row $value)
 */
class GetRowResponse extends \ProtocolBuffers\Message
{
  // @@protoc_insertion_point(traits:.ots.protocolbuffer.GetRowResponse)
  
  /**
   * @var \ots\protocolbuffer\ConsumedCapacity $consumed
   * @tag 1
   * @label required
   * @type \ProtocolBuffers::TYPE_MESSAGE
   **/
  protected $consumed;
  
  /**
   * @var \ots\protocolbuffer\Row $row
   * @tag 2
   * @label required
   * @type \ProtocolBuffers::TYPE_MESSAGE
   **/
  protected $row;
  
  
  // @@protoc_insertion_point(properties_scope:.ots.protocolbuffer.GetRowResponse)

  // @@protoc_insertion_point(class_scope:.ots.protocolbuffer.GetRowResponse)

  /**
   * get descriptor for protocol buffers
   * 
   * @return \ProtocolBuffersDescriptor
   */
  public static function getDescriptor()
  {
    static $descriptor;
    
    if (!isset($descriptor)) {
      $desc = new \ProtocolBuffers\DescriptorBuilder();
      $desc->addField(1, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_MESSAGE,
        "name"     => "consumed",
        "required" => true,
        "optional" => false,
        "repeated" => false,
        "packable" => false,
        "default"  => null,
        "message" => '\ots\protocolbuffer\ConsumedCapacity',
      )));
      $desc->addField(2, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_MESSAGE,
        "name"     => "row",
        "required" => true,
        "optional" => false,
        "repeated" => false,
        "packable" => false,
        "default"  => null,
        "message" => '\ots\protocolbuffer\Row',
      )));
      // @@protoc_insertion_point(builder_scope:.ots.protocolbuffer.GetRowResponse)

      $descriptor = $desc->build();
    }
    return $descriptor;
  }

}
